## ![Logo](https://c.fsdn.com/allura/p/figdice/icon) FigDice Templating System for PHP 
[![Build Status](https://travis-ci.org/figdice/figdice.svg?branch=master)](https://travis-ci.org/figdice/figdice)
[![Latest Stable Version](https://poser.pugx.org/figdice/figdice/v/stable)](https://packagist.org/packages/figdice/figdice)
[![Coverage Status](https://coveralls.io/repos/github/figdice/figdice/badge.svg?branch=master)](https://coveralls.io/github/figdice/figdice?branch=master)
[![@figdice on Twitter](https://img.shields.io/badge/twitter-%40figdice-5189c7.svg)](https://twitter.com/figdice)

# Abstract

FigDice is a templating engine for PHP.
It differs from most of the popular template systems, in the way the presentation data are made available to templates: instead of pushing the data from Controller to View, you build Views that pull the data they need.

See **[figdice.org](http://www.figdice.org/)**

# Try a [Live Demo](http://demo.figdice.org/) now!

Presentation and Tutorial:
- [SitePoint | Getting Started With FigDice](http://www.sitepoint.com/?s=figdice) (English)
- [php[architect] | December 2015](https://www.phparch.com/magazine/2015-2/december/) (English)
- [GNU/Linux Magazine France | 158, March 2013](http://connect.ed-diamond.com/GNU-Linux-Magazine/GLMF-158/FigDice-un-Templating-System-efficace-et-original) (French)

## Features

- **Fast, easy** and powerful Template Engine
- **XML syntax** for your Templates: you gain built-in validation
- Instructions are extended attributes inside your HTML tags: you can display your templates **WYSIWYG** in your browser/editor
- Manipulate your data with the help of a simple and powerful **expression parser**
- Built-in **i18n**, using keys/values from cached XML dictionaries
- Inclusions, loops, conditions, with a **non-intrusive syntax** inside the document
- No programming required, for the Template designers
- Hermetic separation between the application's layers (Presentation / Logics)
- Inversion of control: the Templates pull the data on-demand. The controllers need not know the templates by heart beforehand

# Installation

Choose among the 3 methods below, which suits better your needs: Composer, Phar, or Zip file.

## 1. Composer

Simply add the following composer.json file to your project root, or append the "require" section to your existing composer.json file:

    {
      "require": {
        "figdice/figdice": "~2.3"
      }
    }

Then run the following command in your project folder:

    php composer.phar install

The **\figdice** namespace is made available thanks to the **autoload** Composer feature.



## 2. Phar

Download the latest [FigDice phar](https://github.com/gabrielzerbib/figdice/releases/download/2.3.4/figdice-2.3.4.phar) file to the location of your choice.
Then, in your source files where you need to use FigDice features, write the line:

    require_once 'phar:///path/to/figdice.phar';

The phar file's stub registers an **autoload** function for the classes in the **\figdice** namespace. Notice that, if you already have an old-style **__autoload** function, you must register it with [spl_autoload_register](http://php.net/manual/en/function.spl-autoload-register.php) before importing the phar.



## 3. Zip

Download the latest Figdice zip file to the location of your choice and extract the archive. Then, *require_once* the **autoload.php** file at the root of the FigDice folder.




# Getting Started

Browse the [examples](https://github.com/gabrielzerbib/figdice/tree/master/examples)!

See [Wiki](https://github.com/gabrielzerbib/figdice/wiki) for more details.




