<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
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

  /** @var  View */
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

    $this->view = new View();
    $this->view->loadFile(dirname(__FILE__).'/resources/DictionaryTest.xml');
    $this->view->setTranslationPath(dirname(__FILE__).'/resources/dict');
		$this->view->setLanguage('fr');
		$expected = "Ma chaîne traduite";
		$this->assertEquals($expected, trim($this->view->render()) );
	}

	public function testOmittedKeyUsesContents() {
	  $xml = <<<ENDXML
<fig:xml>
  <fig:dictionary file="test-dic.xml" />
	<fig:trans>my-key</fig:trans>
</fig:xml>
ENDXML;
	  $this->view->loadString($xml);

	  $this->view->setTranslationPath(dirname(__FILE__).'/resources/dict');
	  $this->view->setLanguage('en');
	  $expected = "My translated string";
	  $this->assertEquals($expected, trim($this->view->render()) );
	}

	public function testDictionaryLoadedFromIncludedTemplate()
  {
    $this->view->loadFile(__DIR__.'/resources/DictionaryTestParent.xml');
    $this->view->setTranslationPath(__DIR__.'/resources/dict');
    $this->view->setLanguage('fr');
    $output = trim($this->view->render());
    $output = preg_replace('#^[ \t]+#m', '', $output);
    $output = preg_replace('#[ \t]+$#m', '', $output);
    $output = preg_replace('#\n+#', ';', $output);
    $this->assertEquals('key1-child;Ma chaîne traduite;key1-parent;key1-parent', $output);

    // Now check that the key1-parent has been cached, because used twice
    // But because the $cache array is private in Dictionary class,
    // we use Reflection to break into it.
    $reflector = new ReflectionClass(get_class(
        // I know for a fact, that my test XML Parent template loads an "inParent" dic.
      $dict = $this->view->getRootNode()->getCurrentFile()->getDictionary('inParent'))
    );
    $cacheProp = $reflector->getProperty('cache');
    $cacheProp->setAccessible(true);
    $cache = $cacheProp->getValue($dict);
    // And I know for a fact that it translates twice the "key1" key.
    $this->assertEquals('key1-parent', $cache['key1']);
  }
}
