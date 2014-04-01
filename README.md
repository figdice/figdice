Starting to work with FigDice in your project is simple.

# Installation

Choose among the 3 methods below, which suits better your needs: Composer, Phar, or Zip file.

## 1. Composer

Simply add the following composer.json file to your project root, or append the "require" section to your existing composer.json file:

    {
      "require": {
        "figdice/figdice": "*"
      }
    }

Then run the following command in your project folder:

    php composer.phar install

The **\figdice** namespace will automatically become available thanks to the **autoload** Composer feature.



## 2. Phar

Download the latest [FigDice phar](https://sourceforge.net/projects/figdice/files/) file to the location of your choice.
Then, in your source files where you need to use FigDice features, write the line:

    require_once 'phar:///path/to/figdice.phar';

The phar file's stub registers an **autoload** function for the classes in the **\figdice** namespace. Notice that, if you already have an old-style **__autoload** function, you must register it with [spl_autoload_register](http://php.net/manual/en/function.spl-autoload-register.php) before importing the phar.

Your will also need to download and include a [PSR-3 Logger implementation](https://github.com/php-fig/log).



## 3. Zip

Download the latest Figdice zip file to the location of your choice and extract the archive. Then you have two options. In both, your will also need to download and include a [PSR-3 Logger implementation](https://github.com/php-fig/log).

### 3.1 Manual includes

In the your source files where you need to use FigDice features, include the necessary files:

    require_once '/path/to/figdice/View.php';
    require_once '/path/to/figdice/Feed.php';
    require_once '/path/to/figdice/Filter.php';

at the minimum.
Notice that some Exceptions and helper classes might need *require* despite you don't use them explicitly in your code.

### 3.2 Autoload

Simply *require_once* the **autoload.php** file at the root of the FigDice folder.




# Getting Started

Browse the [examples](https://github.com/gabrielzerbib/figdice/tree/master/examples)!

See [Documentation](http://www.figdice.org/en/manual.html) for more details.




