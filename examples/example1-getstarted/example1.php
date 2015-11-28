<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.2
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 * FigDice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * FigDice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FigDice.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * In this example we will learn to:
 *
 * - get started with the library
 * - load a template by its outer container file (This is not the recommended FigDice way!)
 * - push some data into the template
 * - pass objects to templates
 * - control the textual content of tags
 * - inline dynamic data into HTML attributes
 * - embed unparsed content
 */


// Autoload the Figdice lib
require_once '../../vendor/autoload.php';

// Create a Fig View object
$view = new \figdice\View();

// Load its main template file:
try {
	$view->loadFile('template-main.xml');
} catch (\figdice\exceptions\FileNotFoundException $ex) {
	die('template file not found');
}

// Mount some data into our View
//  these values will become available form within the template
//  as: /document/title
//      /document/css/textColor
$view->mount('document', array(
	'title' => 'My first FigDice exercise',
	'css' => array(
		'textColor' => '#dd22aa'
	)
));

// Mount some more data into our View.
//  They could come from a database, for example.
//  NB:  You can mount Objects! provided that the properties you want to
//  expose, have a getter method (or are public).
class MyUser {
  // This public property is accessible from your template
  public $firstname;
  // This private property is automatically made accessible by the getTitle method.
  private $title;
  public function __construct($title, $firstname) {
    $this->title = $title;
    $this->firstname = $firstname;
  }
  public function getTitle() {
    return $this->title;
  }
}

$view->mount('userDetails', new MyUser('Mr', 'Gabriel') );


// Render the template!
try {
	$output = $view->render();
} catch (\figdice\exceptions\FileNotFoundException $ex) {
	die('some include went wrong at rendering-time: ' . PHP_EOL . $ex->getMessage());
}

echo $output;
// Typically to the browser, or into a file for caching purposes, etc.

