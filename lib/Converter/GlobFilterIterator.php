<?php
/**
 * Contains GlobFilterIterator class.
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

/**
 * Class GlobFilterIterator
 */
class GlobFilterIterator extends \FilterIterator
{
    /**
     * @param \FilesystemIterator $iterator
     * @param string              $glob
     * @param string[]            $excludedList
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        \FilesystemIterator $iterator,
        $glob,
        array $excludedList = array()
    ) {
        $this->setGlob($glob);
        $this->setExcludedList($excludedList);
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
        if ($current->isDir()) {
            return false;
        }
        if (!preg_match($this->glob, $current->getFilename())) {
            return false;
        }
        if (empty($this->excludedList)) {
            return true;
        }
        foreach ($this->excludedList as $regExp) {
            if (preg_match($regExp, $current->getFilename())) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param string[] $value
     *
     * @return self
     */
    public function setExcludedList(array $value)
    {
        $this->excludedList = $this->convertGlobsToRegExp($value);
        return $this;
    }
    /**
     * @param string $value
     *
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @return self
     */
    public function setGlob($value)
    {
        if (!is_string($value)) {
            $mess = 'Glob MUST be a string but given ' . gettype($value);
            throw new \InvalidArgumentException($mess);
        }
        $this->glob = $this->convertGlobToRegExp($value);
        return $this;
    }
    /**
     * @var string
     */
    protected $allowedNameChars = '/^(?:[[:alnum:]]|[ _.*?-])+$/';
    /**
     * @var string[]
     */
    protected $excludedList;
    /**
     * @var string
     */
    protected $glob;
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
        if (!preg_match($this->allowedNameChars, $glob)) {
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
     * Used to convert shell style globs into RegExp.
     *
     * @param string[] $globs
     *
     * @throws \DomainException
     * @return string[]
     */
    protected function convertGlobsToRegExp(array $globs)
    {
        if (empty($globs)) {
            return array();
        }
        $converted = array();
        foreach ($globs as $glob) {
            $converted[] = $this->convertGlobToRegExp($glob);
        }
        return $converted;
    }
}
