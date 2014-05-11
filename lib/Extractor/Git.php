<?php
/**
 * Contains Git class.
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
namespace peol\Extractor;

use peol\Exception\PeolEolException;
use peol\Exception\PeolFileException;

/**
 * Class Git
 */
class Git implements ExtractorInterface
{
    /**
     * Used to extract end of line globMap and excluded file list information from file.
     *
     * @param string $fileName
     *
     * @throws PeolEolException
     * @throws PeolFileException
     * @return self
     */
    public function extractFromFile($fileName)
    {
        if (!is_readable($fileName) || !is_file($fileName)) {
            $mess = 'File NOT accessible given ' . $fileName;
            throw new PeolFileException($mess);
        }
        $contents = file_get_contents($fileName);
        if ($contents === false) {
            $mess = 'Could NOT get contents of ' . $fileName;
            throw new PeolFileException($mess);
        }
        try {
            $this->extractFromString($contents);
        } catch (PeolEolException $exp) {
            $mess = $exp->getMessage() . ' in file ' . $fileName;
            throw new PeolEolException($mess, 0, $exp);
        }
        return $this;
    }
    /**
     * Used to extract end of line globMap and excluded file list information from string.
     *
     * @param string $contents
     *
     * @throws \InvalidArgumentException
     * @throws PeolEolException
     * @return self
     */
    public function extractFromString($contents)
    {
        if (empty($contents)) {
            return $this;
        }
        $contents = $this->explodeStringToArray($contents);
        foreach ($contents as $line) {
            $line = $this->deleteSharpComment($line);
            $line = $this->cleanupLineWhiteSpace($line);
            // If ended up with empty line ignore it.
            if (empty($line)) {
                continue;
            }
            list($glob, $attributes) = explode(' ', $line, 2);
            // Exclude anything that is NOT considered a text file.
            $needles = array('-text', '-crlf', 'binary');
            if ($this->containsNeedles($needles, $attributes)) {
                $this->addToExcludedFileList($glob);
                continue;
            }
            if ($this->containsNeedles('eol=lf', $attributes)) {
                $this->addToEndOfLineMap($glob, $this->eolMap['lf']);
            } elseif ($this->containsNeedles('eol=crlf', $attributes)) {
                $this->addToEndOfLineMap($glob, $this->eolMap['crlf']);
            } elseif ($this->containsNeedles('text=auto', $attributes)) {
                if (strlen(PHP_EOL) > 1) {
                    $this->addToEndOfLineMap($glob, $this->eolMap['crlf']);
                } else {
                    $this->addToEndOfLineMap($glob, $this->eolMap['lf']);
                }
            }
        }
        return $this;
    }
    /**
     * Return a file name to end of line globMap (list)
     *
     * File names may also use shell style "*" globs like the following:
     * <pre>
     * *.txt
     * a*.txt
     * abc.*
     * </pre>
     *
     * The returned array (globMap) will have file name as key and new line ending
     * as value for example:
     * <pre>
     * array(
     * '*.php' => "\n",
     * 'README.md => "\r\n"
     * )
     * </pre>
     *
     * @return string[]
     */
    public function getEndOfLineMap()
    {
        return $this->endOfLineMap;
    }
    /**
     * Returns a list of excluded file names.
     *
     * File names may also use shell style "*" globs like the following:
     * <pre>
     * *.txt
     * a*.txt
     * abc.*
     * </pre>
     *
     * @return string[]
     */
    public function getExcludedFilesList()
    {
        return $this->excludedFilesList;
    }
    /**
     * Called to find out if class wants to handle the file.
     *
     * @param string $fileName Full path and file name with extension.
     *
     * @return bool
     */
    public function isExtractor($fileName)
    {
        return (bool)basename($fileName) == '.gitattributes';
    }
    /**
     * @var string[]
     */
    protected $endOfLineMap = array();
    /**
     * @var string[]
     */
    protected $eolMap = array(
        'crlf' => "\r\n",
        'lf' => "\n",
        'cr' => "\r"
    );
    /**
     * @var string[]
     */
    protected $excludedFilesList;
    /**
     * @param $fileName
     * @param $eolStyle
     *
     * @throws \InvalidArgumentException
     * @return self
     */
    protected function addToEndOfLineMap($fileName, $eolStyle)
    {
        if (!is_string($fileName)) {
            $mess =
                '$fileName MUST be a string but given ' . gettype($fileName);
            throw new \InvalidArgumentException($mess);
        }
        if (!is_string($eolStyle)) {
            $mess =
                '$eolStyle MUST be a string but given ' . gettype($eolStyle);
            throw new \InvalidArgumentException($mess);
        }
        if (!array_key_exists($fileName, $this->endOfLineMap)) {
            $this->endOfLineMap[$fileName] = $eolStyle;
        }
        return $this;
    }
    /**
     * @param string $fileName
     *
     * @throws \InvalidArgumentException
     * @return self
     */
    protected function addToExcludedFileList($fileName)
    {
        if (!is_string($fileName)) {
            $mess =
                '$fileName MUST be a string but given ' . gettype($fileName);
            throw new \InvalidArgumentException($mess);
        }
        if (!in_array($fileName, $this->excludedFileList)) {
            $this->excludedFileList[] = $fileName;
        }
        return $this;
    }
    /**
     * @param string $line
     *
     * @return string
     */
    protected function cleanupLineWhiteSpace($line)
    {
        // Replace any tabs with spaces.
        $line = str_replace("\t", ' ', $line);
        // Replace any multiple consecutive spaces with single space.
        while (strpos($line, ' ' . ' ') !== false) {
            $line = str_replace(' ' . ' ', ' ', $line);
        }
        return trim($line);
    }
    /**
     * @param string|array $needles
     * @param string       $target
     *
     * @return bool Returns true if $needles is found in $target else false.
     */
    protected function containsNeedles($needles, $target)
    {
        if (is_string($needles)) {
            $needles = (array)$needles;
        }
        $originalLength = strlen($target);
        $target = str_replace($needles, '', $target);
        return (bool)$originalLength != strlen($target);
    }
    /**
     * @param string $line
     *
     * @return string
     */
    protected function deleteSharpComment($line)
    {
        $pos = strpos($line, "#");
        // Ignore comment line.
        if ($pos === 0) {
            $line = '';
            // Trim end comment.
        } elseif ($pos !== false) {
            $line = substr($line, 0, ($pos - 1));
        }
        return $line;
    }
    /**
     * @param string $data
     *
     * @throws PeolEolException
     * @return array
     */
    protected function explodeStringToArray($data)
    {
        if (strpos($data, "\n") !== false) {
            $eol = "\n";
        } elseif (strpos($data, "\r") !== false) {
            $eol = "\r";
        } else {
            $mess = 'No known end of line characters found';
            throw new PeolEolException($mess);
        }
        return explode($eol, $data);
    }
}
