<?php
/**
 * Contains GlobPathIterator class.
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
 * @license http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author Michael Cummings <mgcummings@yahoo.com>
 */
namespace peol\Converter;

use peol\Exception\PeolPathException;

/**
 * Class GlobPathIterator
 */
class GlobPathIterator extends \FilterIterator
{
    /**
     * @param \FilesystemIterator $iterator
     * @param string              $path
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        \FilesystemIterator $iterator,
        $path
    ) {
        $this->setPath($path);
        parent::__construct($iterator);
    }
    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     */
    public function accept()
    {
        /**
         * @var  \SplFileInfo $current
         */
        $current = parent::current();
        if (!$current->isDir()) {
            return false;
        }
    }
    /**
     * @param string $value
     *
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @return self
     */
    public function setPath($value)
    {
        if (!is_string($value)) {
            $mess = 'Path MUST be a string but given ' . gettype($value);
            throw new \InvalidArgumentException($mess);
        }
        $this->path = $this->convertGlobToRegExp($value);
        return $this;
    }
    /**
     * @var string
     */
    private $path;
    /**
     * @var string[]
     */
    protected $globMap = array('.' => '\\.', '*' => '.*', '?' => '.');
    /**
     * Used to convert shell style glob into RegExp.
     *
     * @param string $glob
     *
     * @throws \DomainException
     * @return string
     */
    protected function convertGlobToRegExp($glob)
    {
        if (strlen($glob) !== strlen(
                str_replace(array('\\', '/'), '', $glob)
            )
        ) {
            $mess = 'Glob contains illegal path component was given ' . $glob;
            throw new \DomainException($mess);
        }
        if (!preg_match($this->allowedPathChars, $glob)) {
            $mess = 'Illegal file name character(s) used was given ' . $glob;
            throw new \DomainException($mess);
        }
        $glob = str_replace(
            array_keys($this->globMap),
            array_values($this->globMap),
            $glob
        );
        return '/^' . $glob . '$/';
    }
    /**
     * @var string
     */
    protected $allowedPathChars = '/^(?:[[:alnum:] _.*?-])+$/';
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
}
