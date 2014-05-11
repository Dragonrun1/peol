Peol
====

Php End Of Line is a group of Non-OS specific PHP commands to change line endings on text files.

## Why Peol ##

Since I do development in Windows as well as Linux systems I needed a cross-platform way to insure that line ending are set
correctly in my projects. Most of the time my IDE does things right but there's time I'll NOT notice it used LF Unix type
endings say in a README file which does need to be in Windows (DOS) CR-LF format or used them in PHP which I prefer to have
in LF instead.

Differences in line endings can also cause issues with VCS if NOT handled correctly as well. In Linux it's no problem to
fix a file or even a group of them using the dos2unix and unit2dos commands in CLI but it's more of an issue in Windows
since it does NOT have those utils. Add to that I've been wanting to learn more about Symfony's Console and this project
was born.

## Installing ##

Peol uses Composer and is up on Packagest so if you also use Composer in your project you can just add it. If you do NOT use
Composer in your project you can still use it to install Peol. First you'll need to install Composer somewhere which you can
get at https://getcomposer.org/. Once you have composer.phar file you can just put it in the directory you want to install
Peol to and add a copy of the composer.json file from GitHub there as well. Once you have both files in the directory you
can run ```php -f "composer.phar" install -o --no-dev```. This should setup Peol so you can use it.

## Using ##

Using Peol is easy for example to change the line endings of all the txt files in the current directory to Windows (DOS):

    php -f where/i/installed/it/bin/peol EolToWin *.txt

For more information on using Peol try ```php -f where/i/installed/it/bin/peol list```.

## Future ##

In the future I'll probably add ability to use shell type glob paths as well but I run into a few snags adding that and
decided that for now it'll do what I need and I've been neglecting my other projects long enough working on it. I included
in the project some WIP on that so if someone else wants to pick it up and finish it they can.
