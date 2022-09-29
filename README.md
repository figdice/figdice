## ![Logo](http://www.figdice.org/img/fig-130-16.png) FigDice Templating System for PHP 
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://github.com/figdice/figdice/actions/workflows/build.yml/badge.svg?branch=4.x)](https://github.com/figdice/figdice/actions)
[![Latest Stable Version](https://poser.pugx.org/figdice/figdice/v/stable)](https://packagist.org/packages/figdice/figdice)
[![Coverage Status](https://coveralls.io/repos/github/figdice/figdice/badge.svg?branch=4.x)](https://coveralls.io/github/figdice/figdice?branch=4.x)
[![@figdice on Twitter](https://img.shields.io/badge/twitter-%40figdice-5189c7.svg)](https://twitter.com/figdice)

# Abstract

FigDice is a templating engine for PHP.
It differs from most of the popular template systems, with regards to the way the presentation data are made available to the templates: instead of pushing the data from the Controller to the View, you build Views that pull the immutable data that they need.

FigDice focuses on the Web Designer stand-point. Designers and Developers agree together on structure of the self-contained *beans* (called Feeds in FigDice) that Developers make available to Designers, and then the Designers may reuse them anywhere they need, and combine them with other Feeds into pages and macros. The View Controllers in FigDice become generic presenters whcih don't need to know the details of what is presented in what template, since the templates themselves will activate their favorite Feeds to pull their data.


See **[figdice.org](https://www.figdice.org/)**

# Try a [Live Demo](https://demo.figdice.org/) now!

Presentation and Tutorial:
- [SitePoint | Getting Started With FigDice](http://www.sitepoint.com/?s=figdice) (English)
- [php[architect] | December 2015](https://www.phparch.com/magazine/2015-2/december/) (English)
- [GNU/Linux Magazine France | 158, March 2013](http://connect.ed-diamond.com/GNU-Linux-Magazine/GLMF-158/FigDice-un-Templating-System-efficace-et-original) (French)

## Features

- **Fast, easy** and powerful Template Engine
- **HTML syntax** for your Templates: FigDice brings a set of [extended attributes](https://github.com/figdice/figdice/wiki/The-FigDice-markup) to help you construct the logics.
- You can display your templates **WYSIWYG** in your browser/editor
- Manipulate your data with the help of a simple and powerful [expression parser](https://github.com/figdice/figdice/wiki/Expression)
- Built-in [i18n](https://github.com/figdice/figdice/wiki/Internationalization), using keys/values from cached dictionaries
- Inclusions, loops, conditions, with a **non-intrusive syntax** inside the document
- No programming required, for the Template designers
- Hermetic separation between the application's layers (Presentation / Logics)
- Inversion of control: the Templates pull the data on-demand. The controllers need not know the templates by heart beforehand

# Installation

Add the figdice dependency to the `require` section of your `composer.json` file:

    "figdice/figdice": "~3.x-dev"


# Getting Started

Browse the [examples](https://github.com/figdice/figdice-examples)!

See [Wiki](https://github.com/figdice/figdice/wiki) for more details.

