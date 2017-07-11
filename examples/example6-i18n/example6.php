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
 * - work with dictionaries
 * - provide dynamic params for named placeholders in entries
 */


// Autoload the Figdice lib
require_once '../../vendor/autoload.php';

use figdice\View;

$view = new View();

// The template we are loading now, imports a Dictionary.
// Dictionaries are XML files containing entries of key/value pairs.

$view->loadFile('template.xml');

// Let the View know where our Dictionaries are stored.
// For the sake of the example, we keep our dictionaries locally, in 
// this same directory.
// The Dictionaries of all the languages must be stored below one same
// parent folder. This is this parent folder which you specify here.
$view->setTranslationPath( dirname(__FILE__).'/dictionaries' );

// Each provided language must exist in the shape of one sub-folder below
// the Translation Path: the folder names correspond to the target language
// in which you wish to render your view. Therefore, below the /dictionaries
// parent, there is a "fr" folder, in which the French dictionaries are found.
$view->setLanguage( 'fr' );


// Let's give some value to the number of available brown shoes:
$view->mount('stock', array('shoes' => 
  array('brown' => array('Smith 40', 'Weston 43', 'Finkers 43'))
));

$output = $view->render();


echo $output;
