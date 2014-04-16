<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.4
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


use figdice\View;
use figdice\classes\File;
use figdice\classes\ViewElementTag;


/**
 * Unit Test Class for fig i18n
 */
class DictionaryTest extends PHPUnit_Framework_TestCase {

	protected $view;

	protected function setUp() {
		$this->view = new View();
	}
	protected function tearDown() {
		$this->view = null;
	}



	public function testDictionaryAndKeyInTwoLanguages() {
		$this->view->loadFile(dirname(__FILE__).'/resources/DictionaryTest.xml');
		$this->view->setTranslationPath(dirname(__FILE__).'/resources/dict');
		$this->view->setLanguage('en');
		$expected = "My translated string";
		$this->assertEquals($expected, trim($this->view->render()) );

		$this->view->setLanguage('fr');
		$expected = "Ma chaîne traduite";
		$this->assertEquals($expected, trim($this->view->render()) );
	}

}

