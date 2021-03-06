<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 */
declare(strict_types=1);

use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\XMLParsingException;
use PHPUnit\Framework\TestCase;

use figdice\classes\ViewElementCData;
use figdice\View;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Unit Test Class for basic View loading
 */
class ViewParserTest extends TestCase {

  public function testRenderBeforeLoadFails() {
    $view = new View();
    $this->expectException(XMLParsingException::class);
    $view->render();
  }

  public function testSourceWithSimpleXml() {
    $view = new View();
    $source = <<<ENDXML
<xml></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml></xml>', $result);
  }

  public function testAutoClosingTagGetsSpaceBeforeSlash() {
    $view = new View();
    $source = <<<ENDXML
<xml><node attr="12"/></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml><node attr="12" /></xml>', $result);
  }

  public function testFigMuteRemovesTag() {
    $view = new View();
    $source = <<<ENDXML
<xml><node fig:mute="true"/></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml></xml>', $result);
  }

  public function testFigTextStatic() {
    $view = new View();
    $source = <<<ENDXML
<xml><node fig:text="12"/></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml><node>12</node></xml>', $result);
  }

  public function testFigTextWithSimpleExpression() {
    $view = new View();
    $source = <<<ENDXML
<xml><node fig:text="12 + 3"/></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml><node>15</node></xml>', $result);
  }


  public function testMountRoot() {
    $view = new View();
    $view->mount('someKey', '12');
    $source = <<<ENDXML
<xml><node fig:text="/someKey + 4"/></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml><node>16</node></xml>', $result);
  }

  public function testMountWithSubpath() {
    $view = new View();
    $view->mount('someKey', array( 'sub' => 'value') );
    $source = <<<ENDXML
<xml><node fig:text="'cool' + /someKey/sub" /></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml><node>coolvalue</node></xml>', $result);
  }

  public function testMountWithRelativeAtRootContext() {
    $view = new View();
    $view->mount('someKey', array( 'sub' => 'value') );
    $source = <<<ENDXML
<xml><node fig:text="'cool' + someKey/sub" /></xml>
ENDXML;
    $view->loadString($source);
    $result = $view->render();
    $this->assertEquals('<xml><node>coolvalue</node></xml>', $result);
  }

  public function testSimpleVoidTag() {
    $view = new View();
    $source = <<<ENDXML
<div>
  <img src="image.jpg" fig:void="true"/>
</div>
ENDXML;

    $view->loadString($source);
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
    $source = <<<ENDXML
<div>
  <img src="image.jpg" fig:void="true">
		<fig:attr name="border" value="1 + 1" />
	</img>
</div>
ENDXML;

    $view->loadString($source);
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
    $source = <<<ENDXML
<div>
  <fig:sometag someattr="1">sometext</fig:sometag>
</div>
ENDXML;

    $view->loadString($source);
    $result = $view->render();
    $expected = <<<ENDHTML
<div>
  sometext
</div>
ENDHTML;

    $this->assertEquals($expected, $result);
  }

  public function testIncludeWithFileNotFoundThrowsException()
  {

    $view = new View();
    $this->expectException(FileNotFoundException::class);
    $view->loadFile('resources/FigXmlIncludeNotFound.xml');
  }
	
  public function testParseAfterRenderHasNoEffect() {
    $view = new View();
    $source = <<<ENDXML
<div>
</div>
ENDXML;
    $view->loadString($source);
    $output = $view->render();
    $view->parse();
    $this->assertEquals($output, $view->render());
  }

  public function testAdHocEval()
  {
    $view = new View();
    $view->loadString('<xml attr="some {adhoc} here"></xml>');
    $view->mount('adhoc', 'test');
    $this->assertEquals('<xml attr="some test here"></xml>', $view->render());
  }

  public function testWalkOnNonCountableObjectRunsOnArrayWithObject()
  {
    $view = new View();
    $view->loadString(
      '<test fig:walk="/obj"><fig:mute fig:text="property" /></test>'
    );
    $obj = new stdClass();
    $obj->property = 12;
    $view->mount('obj', $obj);
    $this->assertEquals('<test>12</test>', $view->render());
  }

  public function testDoctype()
  {
    $view = new View();
    $templateSource = <<<ENDXML
<tpl:template xmlns:tpl="http://figdice.org" tpl:doctype="html">
<html></html>
</tpl:template>
ENDXML;
    $view->loadString($templateSource);

    $expected = <<<EXPECTED
<!doctype html>
<html></html>

EXPECTED;

    $this->assertEquals($expected, $view->render());
  }
  public function testDoctypeOnNonRootNodeHasNoEffect()
  {
    $view = new View();
    $templateSource = <<<ENDXML
<html fig:doctype="html">
  <head fig:doctype="dummy"></head>
</html>
ENDXML;
    $view->loadString($templateSource);

    $expected = <<<EXPECTED
<!doctype html>
<html>
  <head></head>
</html>
EXPECTED;

    $rendered = $view->render();
    $this->assertEquals($expected, $rendered);
  }
    public function testDoctypeOnIncludedViewReplacesExisting()
    {

        $template1 =
            '<fig:x>'."\n".
            '   <div fig:plug="main">'."\n".
            '   </div>'."\n".
            '   <fig:include file="outer.html"/>'."\n".
            '</fig:x>';

        $template2 =
            '<html fig:doctype="html">'."\n".
            '   <div fig:slot="main"></div>'."\n".
            '</html>';

        vfsStream::setup('root');
        vfsStream::newFile('template1.html')->withContent($template1)->at(vfsStreamWrapper::getRoot());
        vfsStream::newFile('outer.html')->withContent($template2)->at(vfsStreamWrapper::getRoot());

        $view = new View();
        $view->loadFile(vfsStream::url('root/template1.html'));
        $rendered = $view->render();

        $expected =
            '<!doctype html>'."\n".
            "   \n".
            '   <html>'."\n".
            '   <div>'."\n".
            '   </div>'."\n".
            '</html>'."\n"
            ;
        $this->assertEquals($expected, $rendered);
    }

    public function testSquashingTreeOfInertNodesCollapsesToSingleCData()
    {
        $template = <<<ENDTEMPLATE
<a>
    <b attr1="val1" attr2="val2">
        <c>
            cdata
        </c>
        <d> xxx </d> <e attr3="val3" />
    </b>
</a>
ENDTEMPLATE;

        $view = new View();
        $view->loadString($template);
        $view->parse();

        $this->assertTrue($view->getRootNode() instanceof ViewElementCData);


    }

    public function testSquashingOfComplexActiveTreeCollapsesToChildren()
    {
        $template = <<<ENDTEMPLATE
<html xmlns:xx="http://figdice.org/" xx:doctype="html">
  <head>
    <meta name="viewport" content="value" xx:void="true"/>
    <title xx:text="'title'"></title>
  </head>
  <body>
    <div class="display">
      <b>inert bold</b>
      <span class="show_{adhoc}"> span </span> <div> inert div </div>
    </div>
  </body>
</html>
ENDTEMPLATE;

        $view = new View();
        $view->loadString($template);
        $view->mount('adhoc', 'show');
        $output = $view->render();

        $expected = <<<ENDEXPECTED
<!doctype html>
<html>
  <head>
    <meta name="viewport" content="value">
    <title>title</title>
  </head>
  <body>
    <div class="display">
      <b>inert bold</b>
      <span class="show_show"> span </span> <div> inert div </div>
    </div>
  </body>
</html>
ENDEXPECTED;

        $this->assertEquals($expected, $output);
    }
}
