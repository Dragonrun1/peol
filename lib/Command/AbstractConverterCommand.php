<?php
/**
 * Contains AbstractConverterCommand class.
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
namespace peol\Command;

use peol\Converter\ConverterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractConverterCommand
 */
abstract class AbstractConverterCommand extends Command
{
    /**
     * @param string|null        $name The name of the command; passing null
     *                                 means it MUST be set in configure()
     * @param null|string        $cwd
     * @param ConverterInterface $converter
     *
     * @throws \LogicException
     */
    public function __construct(
        $name = null,
        $cwd,
        ConverterInterface $converter
    ) {
        $this->setCwd($cwd);
        $this->setConverter($converter);
        parent::__construct($name);
    }
    /**
     * @param ConverterInterface $value
     *
     * @return self
     */
    public function setConverter(ConverterInterface $value)
    {
        $this->converter = $value;
        return $this;
    }
    /**
     * @param string $value
     *
     * @throws \InvalidArgumentException
     * @return self
     */
    public function setCwd($value)
    {
        if (!is_string($value)) {
            $mess = 'Cwd MUST be string but given ' . gettype($value);
            throw new \InvalidArgumentException($mess);
        }
        $this->cwd = $value;
        return $this;
    }
    /**
     * @var ConverterInterface
     */
    protected $converter;
    /**
     * @var string
     */
    protected $cwd;
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->addArgument(
            'file',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'File(s) to be converted. Wildcard names may be used.'
        );
        $help = <<<EOF
The <info>%command.name%</info> command converts text files end of line format.
If no arguments are used <info>%command.name%</info> converts *.txt file(s) in
the current working directory.

    <info>php %command.full_name%</info>

EXAMPLES:
Converting and replacing text file(s) in the current working directory.
    <info>%command.name%</info>
    <info>%command.name% *.txt</info>

Converting and replacing a.txt.
    <info>%command.name% a.txt</info>

Converting and replacing a.txt and converting and replacing b.txt.
    <info>%command.name% a.txt b.txt</info>

Using directory with the following files:
    <comment>
    a.txt
    aa.txt
    b.txt
    </comment>
Converting and replacing using "*" wildcard:
    <info>%command.name% *.txt</info>

Result:
    <comment>
    a.txt (converted)
    aa.txt (converted)
    b.txt (converted)
    </comment>
Converting and replacing using prefixed wildcard:
    <info>%command.name% a*.txt</info>

Result:
    <comment>
    a.txt  (converted)
    aa.txt (converted)
    b.txt  (NOT converted)</comment>
EOF;
        $this->setHelp($help);
    }
    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|integer null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException
     * @see    setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('file')) {
            $args = array_unique($input->getArgument('file'));
        } else {
            $args = array($this->getCwd() . '/*.txt');
        }
        $eol = $this->getEol();
        if (empty($eol)) {
            $mess =
                'Eol MUST be set in implementing class but was empty when accessed';
            throw new \LogicException($mess);
        }
        $converter = $this->getConverter();
        foreach ($args as $arg) {
            $path = dirname($arg);
            if (!$this->isAbsolutePath($path)) {
                $path = $this->getCwd() . '/' . $path;
            }
            $converter->convertFiles(
                array(basename($arg) => $eol),
                array($path)
            );
        }
        return 0;
    }
    /**
     * @throws \LogicException
     * @return ConverterInterface
     */
    protected function getConverter()
    {
        if (empty($this->converter)) {
            $mess = 'Tried to access converter before it was set';
            throw new \LogicException($mess);
        }
        return $this->converter;
    }
    /**
     * @throws \LogicException
     * @return string
     */
    protected function getCwd()
    {
        if (empty($this->cwd)) {
            $mess = 'Tried to access cwd before it was set';
            throw new \LogicException($mess);
        }
        return str_replace('\\', '/', $this->cwd);
    }
    /**
     * @return string
     */
    abstract protected function getEol();
    /**
     * @param string $path
     *
     * @return bool
     */
    protected function isAbsolutePath($path)
    {
        if (strpos($path, ':') !== false || substr($path, 0, 1) =='/') {
            return true;
        }
        return false;
    }
}
