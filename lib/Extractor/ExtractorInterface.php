<?php
/**
 * Contains ExtractorInterface Interface.
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
 * Interface for extract file name to end of line list and excluded files list from a source.
 */
interface ExtractorInterface
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
    public function extractFromFile($fileName);
    /**
     * Used to extract end of line globMap and excluded file list information from string.
     *
     * @param string $contents
     *
     * @throws PeolEolException
     * @return self
     */
    public function extractFromString($contents);
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
    public function getEndOfLineMap();
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
    public function getExcludedFilesList();
    /**
     * Called to find out if class wants to handle the file.
     *
     * @param string $fileName Full path and file name with extension.
     *
     * @return bool
     */
    public function isExtractor($fileName);
}
