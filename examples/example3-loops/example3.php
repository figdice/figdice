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
 * - work with loops
 * - understand the notion of Context
 * - use the dot symbol, representing the current item.
 */


// Autoload the Figdice lib
require_once '../../vendor/autoload.php';

use figdice\View;

$view = new View();
$view->loadFile('template.xml');

// Mount an plain, one-dimension indexed array into 'indexed' key.
// Each item is an object (or an assoc. array) with 
//  "name" and "link" property.
$view->mount('indexed', array(
		array('name' => 'A', 'link' => 'page_1.html'),
		array('name' => 'B', 'link' => 'page_2.html'),
		array('name' => 'C', 'link' => 'page_3.html')
 ));

// and a nested structure in the 'nested' key
$view->mount('nested', array(
	array('name' => 'X', 'values' => array(11, 14, 17)),
	array('name' => 'Y', 'values' => array(2,   4,  6))
));

// Render the template!
$output = $view->render();

echo $output;
