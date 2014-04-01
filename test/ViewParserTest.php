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

use figdice\View;

/**
 * Unit Test Class for basic View loading
 */
class ViewParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \figdice\exceptions\XMLParsingException
	 */
	public function testRenderBeforeLoadFails() {
		$view = new View();
		$view->source = '';
		$result = $view->render();
		$this->assertFail();
	}

	public function testSourceWithSimpleXml() {
		$view = new View();
		$view->source = <<<ENDXML
<xml></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml></xml>', $result);
	}

	public function testAutoClosingTagGetsSpaceBeforeSlash() {
		$view = new View();
		$view->source = <<<ENDXML
<xml><node attr="12"/></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml><node attr="12" /></xml>', $result);
	}

	public function testFigMuteRemovesTag() {
		$view = new View();
		$view->source = <<<ENDXML
<xml><node fig:mute="true"/></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml></xml>', $result);
	}

	public function testFigTextStatic() {
		$view = new View();
		$view->source = <<<ENDXML
<xml><node fig:text="12"/></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml><node>12</node></xml>', $result);
	}

	public function testFigTextWithSimpleExpression() {
		$view = new View();
		$view->source = <<<ENDXML
<xml><node fig:text="12 + 3"/></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml><node>15</node></xml>', $result);
	}


	public function testMountRoot() {
		$view = new View();
		$view->mount('someKey', '12');
		$view->source = <<<ENDXML
<xml><node fig:text="/someKey + 4"/></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml><node>16</node></xml>', $result);
	}

	public function testMountWithSubpath() {
		$view = new View();
		$view->mount('someKey', array( 'sub' => 'value') );
		$view->source = <<<ENDXML
<xml><node fig:text="'cool' + /someKey/sub" /></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml><node>coolvalue</node></xml>', $result);
	}

	public function testMountWithRelativeAtRootContext() {
		$view = new View();
		$view->mount('someKey', array( 'sub' => 'value') );
		$view->source = <<<ENDXML
<xml><node fig:text="'cool' + someKey/sub" /></xml>
ENDXML;
		$result = $view->render();
		$this->assertEquals('<xml><node>coolvalue</node></xml>', $result);
	}

	public function testSimpleVoidTag() {
		$view = new View();
		$view->source = <<<ENDXML
<div>
  <img src="image.jpg" fig:void="true"/>
</div>
ENDXML;

		$result = $view->render();
		$expected = <<<ENDHTML
<div>
  <img src="image.jpg">
</div>
ENDHTML;

		$this->assertEquals($expected, $result);
	}

	public function testVoidTagWithInnerAttributes() {
		$view = new View();
		$view->source = <<<ENDXML
<div>
  <img src="image.jpg" fig:void="true">
		<fig:attr name="border" value="1 + 1" />
	</img>
</div>
ENDXML;

		$result = $view->render();
		$expected = <<<ENDHTML
<div>
  <img src="image.jpg" border="2">
</div>
ENDHTML;

		$this->assertEquals($expected, $result);
	}

	public function testFigTagIsAlwaysMute() {
		$view = new View();
		$view->source = <<<ENDXML
<div>
  <fig:sometag someattr="1">sometext</fig:sometag>
</div>
ENDXML;

		$result = $view->render();
		$expected = <<<ENDHTML
<div>
  sometext
</div>
ENDHTML;

		$this->assertEquals($expected, $result);
	}

	/**
	 * @expectedException figdice\exceptions\FileNotFoundException
	 */
	public function testIncludeWithFileNotFoundThrowsException()
	{

		$view = new View();
		$view->loadFile('resources/FigXmlIncludeNotFound.xml');
		
		// will raise an exception
		$result = $view->render();
		$this->assertFalse(true);
	}
}
