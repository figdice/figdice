<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
 * @package FigDice
 *
 * This file is part of FigDice.
 */


/**
 * In this example we will learn to:
 *
 * - work with feeds
 * - understand the inversion of control for data
 */


// Autoload the Figdice lib
require_once '../../vendor/autoload.php';

use figdice\View;

$view = new View();
$view->loadFile('template.xml');

// In this exercise, we are not going to mount any data
// directly into the View's universe. Rather, we will make
// a Feed subclass, and we let the View know that it exists
// (via a FeedFactory), and we will let the Template itself
// decide whether to activate the feed or not (and which Feeds it needs
// for this specific page).

// Our Feed is declared in PrimeNumbersFeed.php
// but we must let our View know about it:
require_once 'Example4FeedFactory.php';
$view->registerFeedFactory(new Example4FeedFactory()); 

// Render the template!
// When the engine will hit the fig:feed tag, it will search our
// registered Feed Factories for the first one capable of instantiating
// the requested Feed, and invoke it in order to obtain the data.
$output = $view->render();

// The whole purpose of separating the data providers from the view
// and from the controller (this file), is that now your controller
// does not have to know in a solid way, what the view's data will be.
// In particular, you can have a master view composed of several tiles (using
// slots, incldues and so on) where each tile is responsible for one thing
// (such as: shopping cart, profile info, presented item to buy, etc.).
// Each tile will invoke its companion feed on-demand, but the master view
// knows nothing about the data that is actually needed, nor your controller.
// Your controller does not push the data into the view: rather, each component
// making up the view will pull the data it knows for sure that it needs.

echo $output;
