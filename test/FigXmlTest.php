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
use figdice\classes\File;
use figdice\classes\ViewElementTag;


/**
 * Unit Test Class for fig tags and attributes
 */
class FigXmlTest extends PHPUnit_Framework_TestCase {

	protected $view;

	protected function setUp() {
		$this->view = new View();
	}
	protected function tearDown() {
		$this->view = null;
	}


	public function testFigVoid()
	{
		$this->view->source = <<<ENDXML
<html>
	<br fig:void="true" />
	<hr fig:void="true"> discarded because forced void </hr>
	<hr />
</html>
ENDXML;

		$expected = <<<ENDHTML
<html>
	<br>
	<hr>
	<hr />
</html>
ENDHTML;

		$this->assertEquals($expected, $this->view->render());
	}

	public function testFigAuto()
	{
		$this->view->source = <<<ENDXML
<html>
	<input name="name" fig:auto="true">
		<fig:attr name="id">myId</fig:attr> Discarded because forced auto
	</input>
</html>
ENDXML;

		$expected = <<<ENDHTML
<html>
	<input name="name" id="myId" />
</html>
ENDHTML;

		$this->assertEquals($expected, $this->view->render());
	}

	public function testFigSingleFileSlotAndPlug() {
    $view = new View();
    $view->loadFile(__DIR__.'/resources/FigXmlSlot.xml');
    
    $output = $view->render();
    $expected = file_get_contents(__DIR__.'/resources/FigXmlSlotExpect.html');
    $this->assertEquals(trim($expected), trim($output));
	}
	
	public function testFigInclude()
	{
		$view = new View();
		$view->loadFile(__DIR__.'/resources/FigXmlInclude1.xml');
		
		$output = $view->render();
		$expected = file_get_contents(__DIR__.'/resources/FigXmlIncludeExpect.html');
		$this->assertEquals(trim($expected), trim($output));
	}
	
	public function testWalkIndexedArray()
	{
		$this->view->source = <<<ENDXML
<fig:x fig:walk="/data">
  <fig:x fig:text="."/>
</fig:x>
ENDXML;
		$this->view->mount('data', array('a', 'b', 'c'));
		$this->assertEquals("a\nb\nc\n", $this->view->render());
	}

	/**
	 * It should be possible to have fig:walk and fig:text on the same tag.
	 * Yet, this is currently not possible because of the way fig:text holds on
	 * the output buffer of the tag -- preventing the next iteration to continue
	 * its job.
	 * The rendering of :walk and :text need refactoring.
	 * 
	 * @expectedException \figdice\exceptions\RenderingException
	 */
	public function testTODOCompactWalkWithIndexedArrayAndTextFails() {
		$this->view->mount('data',  array(1,2,3));
		$this->view->source = <<<ENDXML
<fig:x fig:walk="/data" fig:text="first()"/>
ENDXML;
		$this->view->render();
		$this->assertFalse(true);
	}

	public function testLoadXMLwithUTF8AccentsAndDeclaredEntities()
	{
		$this->view->source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE figdice [
  <!ENTITY eacute "&#233;">
]>
<xml fig:mute="true">
  éà &eacute; €
</xml>
ENDXML;
		$this->view->mount('data', array('a', 'b', 'c'));
		$this->view->setReplacements(false);
		$this->assertEquals("éà é €", trim($this->view->render()) );
	}

	/**
	 * @expectedException figdice\exceptions\XMLParsingException
	 */
	public function testUndeclaredEntitiesRaiseException()
	{
		$this->view->source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE figdice [
  <!ENTITY eacute "&eacute;">
]>
<xml fig:mute="true">
  éà &eacute; € &ocirc;
</xml>
ENDXML;
		$this->view->mount('data', array('a', 'b', 'c'));
		$this->view->setReplacements(false);
		$this->assertEquals("éà &eacute; € &ocirc;", trim($this->view->render()) );
	}
	
	public function testHtmlEntitiesReplacementsByDefault() {
	    $this->view->source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<xml fig:mute="true">
  éà &eacute; € &ocirc;
</xml>
ENDXML;
	    $this->view->mount('data', array('a', 'b', 'c'));
	    $this->assertEquals("éà é € ô", trim($this->view->render()) );
	}


	public function testHtmlEntitiesReplacementsKeepsAmpersandAndLt() {
	    $this->view->source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<xml fig:mute="true">
&ocirc; &lt; &amp;lt;
</xml>
ENDXML;
	    $this->view->mount('data', array('a', 'b', 'c'));
	    $this->assertEquals("ô < &lt;", trim($this->view->render()) );
	}

	/**
	 * @expectedException \figdice\exceptions\RequiredAttributeException
	 */
	public function testMissingRequiredAttributeException() {
	  $this->view->source = <<<ENDXML
<xml>
  <fig:include />
</xml>
ENDXML;
	  $this->assertNull( $this->view->render() );
	}
}
