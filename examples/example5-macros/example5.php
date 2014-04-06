<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
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
 * - work with macros
 */


// Autoload the Figdice lib
require_once '../../vendor/autoload.php';

use \figdice\View;

$view = new View();

// The template we are studying now, uses the Macro tool.
// A Macro is similar to a Function in PHP: it has a name,
// and accepts parameter.
// However it does not "return" a value: rather, it produces
// in-place output, at the location where it is invoked.

$view->loadFile('template.xml');

// We have learned, in example 4, how to use Feeds as data providers
// for our View. In this example we will get back to mounting direct
// data from our Controller into our View, for the sake of simplicity.
// But you know now that it is not the smartest way to go, in the
// FigDice paradigm.

// So let's mount some structured data.
$view->mount('countries', array(
    'France' => array(
        'capital' => 'Paris',
        'wiki' => 'http://en.wikipedia.org/wiki/France'
    ),
    'Germany' => array(
        'capital' => 'Berlin',
        'wiki' => 'http://en.wikipedia.org/wiki/Germany'
    )
));

$output = $view->render();


echo $output;
