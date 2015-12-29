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

namespace figdice\test;

use figdice\exceptions\FeedClassNotFoundException;
use figdice\Feed;
use figdice\View;
use figdice\exceptions\FileNotFoundException;


/**
 * Unit Test Class for fig tags and attributes
 */
class FigXmlTest extends \PHPUnit_Framework_TestCase {

  protected $view;

  protected function setUp() {
    $this->view = new View();
  }
  protected function tearDown() {
    $this->view = null;
  }

  public function testFigFlag()
  {
    $source = <<<ENDXML
<fig><fig:attr name="ng-app" flag="true">bla</fig:attr></fig>
ENDXML;

    $this->view->loadString($source);
    $expected = '<fig ng-app></fig>';
    $this->assertEquals($expected, $this->view->render());
  }

  public function testFigVoid()
  {
    $source = <<<ENDXML
<html>
	<br fig:void="true" />
	<hr fig:void="true"> discarded because forced void </hr>
	<hr />
</html>
ENDXML;

    $this->view->loadString($source);
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
    $source = <<<ENDXML
<html>
	<input name="name" fig:auto="true">
		<fig:attr name="id">myId</fig:attr> Discarded because forced auto
	</input>
</html>
ENDXML;

    $this->view->loadString($source);
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
    $source = <<<ENDXML
<fig:x fig:walk="/data">
  <fig:x fig:text="."/>
</fig:x>
ENDXML;
    $this->view->loadString($source);
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
    $source = <<<ENDXML
<fig:x fig:walk="/data" fig:text="first()"/>
ENDXML;
    $this->view->loadString($source);
    $this->view->render();
    $this->assertFalse(true);
  }

  public function testLoadXMLwithUTF8AccentsAndDeclaredEntities()
  {
    $source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE figdice [
  <!ENTITY eacute "&#233;">
]>
<xml fig:mute="true">
  éà &eacute; €
</xml>
ENDXML;
    $this->view->loadString($source);
    $this->view->mount('data', array('a', 'b', 'c'));
    $this->view->setReplacements(false);
    $this->assertEquals("éà é €", trim($this->view->render()) );
  }

  /**
   * @expectedException \figdice\exceptions\XMLParsingException
   */
  public function testUndeclaredEntitiesRaiseException()
  {
    $source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE figdice [
  <!ENTITY eacute "&eacute;">
]>
<xml fig:mute="true">
  éà &eacute; € &ocirc;
</xml>
ENDXML;
    $this->view->loadString($source);
    $this->view->mount('data', array('a', 'b', 'c'));
    $this->view->setReplacements(false);
    $this->assertEquals("éà &eacute; € &ocirc;", trim($this->view->render()) );
  }
	
  public function testHtmlEntitiesReplacementsByDefault() {
    $source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<xml fig:mute="true">
  éà &eacute; € &ocirc;
</xml>
ENDXML;
    $this->view->loadString($source);
    $this->view->mount('data', array('a', 'b', 'c'));
    $this->assertEquals("éà é € ô", trim($this->view->render()) );
  }


  public function testHtmlEntitiesReplacementsKeepsAmpersandAndLt() {
    $source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<xml fig:mute="true">
&ocirc; &lt; &amp;lt;
</xml>
ENDXML;
    $this->view->loadString($source);
    $this->view->mount('data', array('a', 'b', 'c'));
    $this->assertEquals("ô < &lt;", trim($this->view->render()) );
  }

  /**
   * @expectedException \figdice\exceptions\RequiredAttributeException
   */
  public function testMissingRequiredAttributeException() {
    $source = <<<ENDXML
<xml>
  <fig:include />
</xml>
ENDXML;
    $this->view->loadString($source);
    $this->assertNull( $this->view->render() );
  }
	
  public function testFilter()
  {
    $source = <<<ENDXML
<fig:xml>
  <div fig:filter="TestFilter">
    <a href="one.html">one</a>
  </div>
</fig:xml>
ENDXML;
    $view = new View();
    $view->loadString($source);
    $view->setFilterPath(__DIR__.DIRECTORY_SEPARATOR.'resources');
    $output = $view->render();
	  
    $expected = <<<ENDHTML
<div>
    <a href="two.html">two</a>
  </div>

ENDHTML;
    $this->assertEquals($expected, $output);
  }

  public function testCase()
  {
    $source = <<<ENDXML
<fig:xml>
	<fig:case>
    <div fig:case="false">first</div>
    <div fig:case="true">second</div>
	</fig:case>
</fig:xml>
ENDXML;
    $view = new View();
    $view->loadString($source);
    $output = $view->render();
    $expected = "<div>second</div>";
    $this->assertEquals($expected, trim($output));
  }

  public function testIncludeFileNotFoundException()
  {
    $source = <<<ENDXML
<fig:xml><fig:include file="file-not-found.xml"/></fig:xml>
ENDXML;
    $view = new View();
    $view->loadString($source);
    try {
      $view->render();
    } catch (FileNotFoundException $ex) {
      $this->assertEquals('file-not-found.xml', basename($ex->getFilename()));
    }
  }

  /**
   * @expectedException \figdice\exceptions\RequiredAttributeException
   */
  public function testMissingRequiredAttributeInFigAttr()
  {
    $source = '<xml><fig:attr value="12"/></xml>';
    $view = new View();
    $view->loadString($source);
    $view->render();
    $this->assertTrue(true);
  }

  public function testMacro()
  {
    $source = '<xml><fig fig:macro="macro"><tag attr="{value}"/></fig><call fig:call="macro"><fig:param name="value"><b>test</b></fig:param></call></xml>';
    $view = new View();
    $view->loadString($source);
    $this->assertEquals('<xml><fig><tag attr="&lt;b&gt;test&lt;/b&gt;" /></fig></xml>', $view->render() );
  }

  public function testWalkOnEmptyGivesNothing()
  {
    $source = '<xml fig:walk="/empty"></xml>';
    $view = new View();
    $view->loadString($source);
    $this->assertEquals('', $view->render());
  }

  /**
   * @expectedException \figdice\exceptions\RenderingException
   */
  public function testAttributeEvalsToArrayException()
  {
    $source = '<xml attr="{myArray}"></xml>';
    $view = new View();
    $view->loadString($source);
    $view->mount('myArray', array(4, 5, 6));
    $this->assertEquals('dummy', $view->render());
  }

  public function testFeedWithParamWithoutFactoryWithPreloadedClass()
  {
    $view = new View();
    $view->loadFile(__DIR__.'/resources/FigXmlFeed.xml');
    $this->assertEquals(12, trim($view->render()));
  }

  public function testFeedWithoutFactoryWithAutoload()
  {
    $view = new View();
    $view->loadFile(__DIR__.'/resources/FigXmlFeedAutoload.xml');

    $autoloadFuncrion = function ($className) {
      if ('some\figdice\test\ns\CustomAutoloadFeed' == $className) {
        require_once __DIR__.'/resources/CustomAutoloadFeed.php';
      }
    };

    spl_autoload_register($autoloadFuncrion);

    $this->assertEquals(13, trim($view->render()));

    spl_autoload_unregister($autoloadFuncrion);
  }

  public function testFigMountValue()
  {
    $view = new View();
    $view->loadString(
      '<fig:template>' .
      '<fig:mount target="mnt" value="12"/>' .
      '<fig:mute fig:text="/mnt" />' .
      '</fig:template>'
    );

    $this->assertEquals(12, $view->render());
  }
  public function testFigMountTree()
  {
    $view = new View();
    $view->loadString(
      '<fig:template>' .
      '<fig:mount target="mnt">' .
      '<tag fig:text="1 + 33"></tag>' .
      '</fig:mount>' .
      '<fig:mute fig:text="/mnt" />' .
      '</fig:template>'
    );

    $this->assertEquals('<tag>34</tag>', $view->render());
  }

  /**
   * @expectedException \figdice\exceptions\FeedClassNotFoundException
   */
  public function testFeedClassNotFoundException()
  {
    $view = new View();
    $view->loadString('<fig:feed class="unlikely\NotFoundFeed"/>');
    $view->render();
    $this->assertTrue(false);
  }

public function testPlug()
  {
    $view = new View();
    $templateString = <<<ENDTEMPLATE
<fig:template>
<slot fig:slot="myslot"/>
Hello
<plug fig:plug="myslot">World</plug>
Of
<plug fig:plug="myslot">Wonder</plug>
</fig:template>
ENDTEMPLATE;

    $view->loadString($templateString);

    $check = <<<ENDCHECK
<plug>Wonder</plug>
Hello

Of
ENDCHECK;
    $this->assertEquals(trim( $check ), trim($view->render()) );
  }

  public function testPlugWithoutAppendRemovesSlotTag()
  {
    $view = new View();
    $templateString = <<<ENDTEMPLATE
<fig:template>
<slot fig:slot="myslot">slot, would get removed</slot>
<fig:mute fig:plug="myslot">Plugged!</fig:mute>
</fig:template>
ENDTEMPLATE;

    $view->loadString($templateString);

    $check = "Plugged!";
    $this->assertEquals(trim( $check ), trim($view->render()) );
  }

  public function testPlugAppend()
  {
    $view = new View();
    $templateString = <<<ENDTEMPLATE
<fig:template>
<slot fig:slot="myslot" fig:mute="true"/>
Hello
<plug fig:plug="myslot">World</plug>
Of
<plug fig:plug="myslot" fig:append="true">Wonder</plug>
</fig:template>
ENDTEMPLATE;

    $view->loadString($templateString);

    $check = <<<ENDCHECK
<plug>World</plug><plug>Wonder</plug>
Hello

Of
ENDCHECK;
    $this->assertEquals(trim( $check ), trim($view->render()) );
  }

    public function testPlugSubsequentAppend()
  {
    $view = new View();
    $templateString = <<<ENDTEMPLATE
<fig:template>
  <!-- default content for the slot -->
  <span><fig:mute fig:slot="myslot">Hello, </fig:mute></span>
  <fig:mute fig:plug="myslot" fig:append="true">John</fig:mute>
  <!-- second plug: appending again -->
  <fig:mute fig:plug="myslot" fig:append="true">, Doe</fig:mute>
</fig:template>
ENDTEMPLATE;

    $view->loadString($templateString);

    $check = <<<ENDCHECK
<span>Hello, John, Doe</span>
ENDCHECK;
    $this->assertEquals(trim( $check ), trim($view->render()) );
  }

  public function testNestedLoops()
  {
    $template = <<<ENDTEMPLATE
<fig:mute fig:walk="/outer">
  <fig:mute fig:walk="inner">
    <fig:mute fig:text="../page"/>-<fig:mute fig:text="x"/>
  </fig:mute>
</fig:mute>
ENDTEMPLATE;

    $data = [];
    for ($i = 1; $i <= 5; ++ $i) {
      $inner = [];
      for ($j = 0; $j < 3; ++ $j) {
        $inner []= [ 'x' => $j ];
      }
      $data []= [
        'page' => 10 * $i,
        'inner' => $inner
      ];
    }
    $view = new View();
    $view->loadString($template);
    $view->mount('outer', $data);

    $result = preg_replace('# +#', ' ', str_replace("\n", ' ', trim($view->render())));

    $this->assertEquals('10-0 10-1 10-2 20-0 20-1 20-2 30-0 30-1 30-2 40-0 40-1 40-2 50-0 50-1 50-2', $result);
  }

}


class CustomFeed extends Feed
{
  public function run()
  {
    return ['value' => $this->getParameterInt('some-param')];
  }
}
