<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.2
 * @package FigDice
 *
 * This file is part of FigDice.
 */


/**
 * In this example we will learn to:
 *
 * - load a template by its inner, meaningful part
 * - declare slots and plug content into them
 * - include sub-templates
 * - play with conditions
 * - play with complex attributes
 */


// Autoload the Figdice lib
require_once '../../vendor/autoload.php';

use figdice\View;
use figdice\exceptions\FileNotFoundException;

// Create a Fig View object
$view = new View();

// This time we do not load the outer page:
// rather, our template's "entry-point" is going to be an inner block.
// The inner block different on every URL of our site, but it sits at a
// specific location on the generic page, which remains (almost) identical
// on every URL.
// The inner template is responsible for loading its container.
try {
	$view->loadFile('template-inner.xml');
} catch (FileNotFoundException $ex) {
	die('template file not found');
}

// Mount some data into our View
// You can play with this true/false value and re-run the example,
// to see the difference in output.
$view->mount('isLogged', false);

// Render the template!
try {
	$output = $view->render();
} catch (FileNotFoundException $ex) {
	die('some include went wrong at rendering-time: ' . PHP_EOL 
			. $ex->getMessage());
}

echo $output;
