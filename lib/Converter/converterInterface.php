<?php
/**
 * Contains ConverterInterface Interface.
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

use peol\Exception\PeolEolException;
use peol\Exception\PeolFileException;

/**
 * Interface ConverterInterface
 */
interface ConverterInterface
{
    const OLD_MAC_EOL = "\r";
    const UNIX_EOL = "\n";
    const WIN_EOL = "\r\n";
    /**
     * @param string[]             $endOfLineMap  Associative array of file
     *                                            names and line endings.
     * @param string[]             $paths         List of paths to search for
     *                                            files to convert. If empty
     *                                            uses current/present working
     *                                            directory from getcwd().
     * @param string[]             $excludedFiles List of files that should be
     *                                            excluded from having line
     *                                            endings changed.
     *
     * @throws PeolEolException
     * @throws PeolFileException
     * @return self
     */
    public function convertFiles(
        array $endOfLineMap,
        array $paths = array(),
        array $excludedFiles = array()
    );
    /**
     * @param string $contents
     * @param string $eol
     *
     * @return self
     */
    public function convertString(&$contents, $eol);
}
