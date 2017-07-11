<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.4
 * @package FigDice
 *
 * This file is part of FigDice.
 */


/**
 * In this example we will learn to:
 *
 * - parse and use pieces of XML data.
 */


// Autoload the Figdice lib
require_once '../../vendor/autoload.php';

use figdice\View;

$view = new View();

// Well, everything is explained in the template.xml... Go see!

$view->loadFile('template.xml');
$output = $view->render();

echo $output;
