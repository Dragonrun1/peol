<?php
/**
 * Contains Converter class.
 *
 * PHP version 5.3
 *
 * LICENSE:
 * This file is part of PhpLineEndChanger
 * Copyright (C) 2014 Michael Cummings
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this program. If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE.md file. A copy of the GNU GPL should also be
 * available in the GNU-GPL.md file.
 *
 * @copyright 2014 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace peol\Converter;

use FilesystemIterator;
use FilesystemIterator as FSI;
use peol\Exception\PeolFileException;
use peol\Exception\PeolPathException;

/**
 * Class Converter
 */
class Converter implements ConverterInterface
{
    /**
     * @param string[] $endOfLineMap  Associative array of file names and line
     *                                endings. Shell style globs using "*" are
     *                                allowed.
     * @param string[] $paths         List of paths to search for files to
     *                                convert. If empty uses current/present
     *                                working directory from getcwd().
     * @param string[] $excludedFiles List of files that should be excluded from
     *                                having line endings changed.
     *
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws PeolFileException
     * @throws PeolPathException
     * @return self
     */
    public function convertFiles(
        array $endOfLineMap,
        array $paths = array(),
        array $excludedFiles = array()
    ) {
        $paths = $paths ? : array(getcwd());
        /**
         * @var string $path
         */
        foreach ($paths as $path) {
            $path = $this->normalizePath($path);
            if (!is_readable($path) || !is_dir($path) || !is_writable($path)) {
                continue;
            }
            foreach ($endOfLineMap as $glob => $eol) {
                $filterItr =
                    $this->getNewGlobFilter($path, $glob, $excludedFiles);
                if (empty($filterItr)) {
                    return $this;
                }
                /**
                 * @var \FilesystemIterator $fsi
                 */
                foreach ($filterItr as $fsi) {
                    $fullName = $fsi->getRealPath();
                    if (!$fsi->isWritable()) {
                        $mess = 'File is NOT writable: ' . $fullName;
                        throw new PeolFileException($mess);
                    }
                    $file = $fsi->openFile('rb+', false);
                    if (!$file->flock(LOCK_EX)) {
                        $mess = 'Could NOT get write lock on ' . $fullName;
                        throw new PeolFileException($mess);
                    }
                    try {
                        $tempFile = new \SplTempFileObject();
                        foreach ($file as $line) {
                            $this->convertString($line, $eol);
                            $tempFile->fwrite($line);
                        }
                        $tempFile->fflush();
                        $tempFile->rewind();
                        $file->ftruncate(0);
                        $file->fflush();
                        $file->rewind();
                        foreach ($tempFile as $line) {
                            $file->fwrite($line);
                        }
                        $file->fflush();
                    } catch (\RuntimeException $exp) {
                        $mess = 'File I/O failed on temp file for ' . $fullName;
                        throw new PeolFileException($mess, 0, $exp);
                    }
                    unset($tempFile, $file);
                }
            }
        }
        return $this;
    }
    /**
     * @param string $contents
     * @param string $eol
     *
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @return self
     */
    public function convertString(&$contents, $eol)
    {
        if (empty($contents)) {
            return $this;
        }
        if (!is_string($contents)) {
            $mess = 'Contents MUST be string but given ' . gettype($contents);
            throw new \InvalidArgumentException($mess);
        }
        if (!is_string($eol)) {
            $mess = 'Eol MUST be string but given ' . gettype($eol);
            throw new \InvalidArgumentException($mess);
        }
        if (!in_array(
            $eol,
            array(self::WIN_EOL, self::OLD_MAC_EOL, self::UNIX_EOL)
        )
        ) {
            $mess =
                'Unknown end of line given MUST be one of "\\n", "\\r", or "\\r\\n"';
            throw new \DomainException($mess);
        }
        // Normalize all end of lines to \n
        $contents = str_replace(
            array(self::WIN_EOL, self::OLD_MAC_EOL),
            self::UNIX_EOL,
            $contents
        );
        if ($eol != self::UNIX_EOL) {
            $contents = str_replace(self::UNIX_EOL, $eol, $contents);
        }
        return $this;
    }
    /**
     * @param string $path
     *
     * @return string[]
     * @throws PeolPathException
     */
    protected function cleanPartsPath($path)
    {
        // Drop all leading and trailing "/"s.
        $path = trim($path, '/');
        // Drop pointless consecutive "/"s.
        while (false !== strpos($path, '//')) {
            str_replace('//', '/', $path);
        }
        // Drop pointless consecutive "*"s.
        while (false !== strpos($path, '**')) {
            str_replace('**', '*', $path);
        }
        $parts = array();
        $hasGlob = false;
        foreach (explode('/', $path) as $part) {
            if ('.' == $part || '' == $part) {
                continue;
            }
            if ('..' == $part) {
                if ($hasGlob) {
                    $mess =
                        'Can NOT use ancestor path after glob start but given '
                        . $path;
                    throw new PeolPathException($mess);
                }
                if (count($parts) < 1) {
                    $mess = 'Can NOT go above root path but given ' . $path;
                    throw new PeolPathException($mess);
                }
                array_pop($parts);
                continue;
            }
            if (false !== strpos($part, '*') || false !== strpos($part, '?')) {
                $hasGlob = true;
            }
            $parts[] = $part;
        }
        return $parts;
    }
    /**
     * @param string   $path
     * @param string   $glob
     * @param string[] $excludedFiles
     *
     * @return GlobFilterIterator
     */
    protected function getNewGlobFilter($path, $glob, array $excludedFiles)
    {
        $flags =
            FSI::CURRENT_AS_FILEINFO | FSI::SKIP_DOTS | FSI::UNIX_PATHS;
        return new GlobFilterIterator(
            new FilesystemIterator($path, $flags), $glob, $excludedFiles
        );
    }
    /**
     * @param string $path
     * @param bool $absoluteRequired
     *
     * @throws PeolPathException
     * @return string
     */
    protected function normalizePath($path, $absoluteRequired = true)
    {
        $path = str_replace('\\', '/', $path);
        // Optional wrapper(s).
        $regExp = '%^(?<wrappers>(?:[[:alpha:]][[:alnum:]]+://)*)';
        // Optional root prefix.
        $regExp .= '(?<root>(?:[[:alpha:]]:/|/)?)';
        // Actual path.
        $regExp .= '(?<path>(?:[[:alnum:] *?_-]*|\.\.|\.)'
            . '(?:/(?:[[:alnum:] *?_-]*|\.\.|\.|/))*)$%';
        $parts = array();
        if (!preg_match($regExp, $path, $parts)) {
            $mess = 'Path is not valid was given ' . $path;
            throw new PeolPathException($mess);
        }
        $wrappers = $parts['wrappers'];
        if ($absoluteRequired && empty($parts['root'])) {
            $mess = 'Path NOT absolute missing drive or root given ' . $path;
            throw new PeolPathException($mess);
        }
        $root = $parts['root'];
        $parts = $this->cleanPartsPath($parts['path']);
        $path = $wrappers . $root . implode('/', $parts);
        if ('/' != substr($path, -1)) {
            $path .= '/';
        }
        return $path;
    }
}
