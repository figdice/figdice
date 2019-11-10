<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 */

declare(strict_types=1);
namespace figdice\test;

use figdice\exceptions\FeedClassNotFoundException;
use figdice\exceptions\RenderingException;
use figdice\exceptions\RequiredAttributeException;
use figdice\exceptions\XMLParsingException;
use PHPUnit\Framework\TestCase;

use figdice\Feed;
use figdice\Filter;
use figdice\FilterFactory;
use figdice\View;
use figdice\exceptions\FileNotFoundException;


/**
 * Unit Test Class for fig tags and attributes
 */
class FigXmlTest extends TestCase
{

    /** @var View */
    protected $view;

    protected function setUp(): void {
        $this->view = new View();
    }
    protected function tearDown(): void {
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
    $this->expectException(XMLParsingException::class);

    $this->view->render();
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

  public function testMissingRequiredAttributeException() {
    $source = <<<ENDXML
<xml>
  <fig:include />
</xml>
ENDXML;
    $this->view->loadString($source);
    $this->expectException(RequiredAttributeException::class);
    $this->view->render();
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
    $view->setFilterFactory(new TestFilterFactory(__DIR__.'/resources'));
    $output = $view->render();
	  
    $expected = <<<ENDHTML
  <div>
    <a href="two.html">two</a>
  </div>

ENDHTML;
    $this->assertEquals($expected, $output);
  }


  public function testCond()
  {
      $source1 = <<<ENDXML
<html>
<a fig:cond="12">Yes</a>
<b fig:cond="2 - 2">No</b>
</html>
ENDXML;

      $view = new View();
      $view->loadString($source1);
      $output = $view->render();

      $expected = <<<ENDEXPECTED
<html>
<a>Yes</a>

</html>
ENDEXPECTED;
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

  public function testMissingRequiredAttributeInFigAttr()
  {
    $source = '<xml><fig:attr value="12"/></xml>';
    $view = new View();
    $view->loadString($source);
    $this->expectException(RequiredAttributeException::class);
    $view->render();
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

  public function testAttributeEvalsToArrayException()
  {
    $source = '<xml attr="{myArray}"></xml>';
    $view = new View();
    $view->loadString($source);
    $view->mount('myArray', array(4, 5, 6));
    $this->expectException(RenderingException::class);
    $view->render();
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

  public function testFeedClassNotFoundException()
  {
    $view = new View();
    $view->loadString('<fig:feed class="unlikely\NotFoundFeed"/>');
    $this->expectException(FeedClassNotFoundException::class);
    $view->render();
  }


  public function testPlugAppend()
  {
    $view = new View();
    $templateString = <<<ENDTEMPLATE
<fig:template>
<slot fig:slot="myslot"/>
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


  public function testXmlnsFigIsNotRendered()
  {
    $template = <<<ENDTEMPLATE
<html xmlns:fig="http://figdice.org/">
</html>
ENDTEMPLATE;

    $view = new View();
    $view->loadString($template);
    $actual = $view->render();

    $expected = "<html>\n</html>";
    $this->assertEquals($expected, $actual);
  }

    public function testIndentationInLoopIsRespected()
    {
        $view = new View();
        $source = <<<ENDSOURCE
<fig>
  <ul>
    <li fig:walk="/array" fig:text="."></li>
  </ul>
</fig>
ENDSOURCE;


        $expected = <<<ENDEXPECTED
<fig>
  <ul>
    <li>1</li>
    <li>2</li>
    <li>3</li>
  </ul>
</fig>
ENDEXPECTED;


        $view->loadString($source);
        $view->mount('array', [1, 2, 3]);
        $output = $view->render();
        $this->assertEquals($expected, $output);
    }

    public function testFigCDataWithoutFileAttrRaisesException()
    {
        $view = new View();
        $view->loadString('<fig:cdata other-attr="dummy" />');
        $this->expectException(RequiredAttributeException::class);
        $view->render();
    }

    public function testFigCDataOfFileNotFoundRaisesException()
    {
        $view = new View();
        $view->loadString('<fig:cdata file="not-found" />');
        $this->expectException(FileNotFoundException::class);
        $view->render();
    }

    public function testFigValReturnsEvaluatedAttribute()
    {
        $view = new View();
        $view->loadString('<fig:val text="expr" />');
        $view->mount('expr', 'value');
        $output = $view->render();
        $this->assertEquals('value', $output);
    }

    public function testFigValWithFalseConditionReturnsNothing()
    {
        $view = new View();
        $view->loadString('<fig:val fig:cond="false" text="expr" />');
        $view->mount('expr', 'value');
        $output = $view->render();
        $this->assertEquals('', $output);
    }
}


class CustomFeed extends Feed
{
  public function run()
  {
    return ['value' => $this->getParameterInt('some-param')];
  }
}

class TestFilterFactory implements FilterFactory
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Called by the ViewElementTag::applyOutputFilter method,
     * to instanciate a filter by its class name.
     *
     * @param string $className
     * @return Filter
     */
    public function create($className)
    {
        require $this->directory.'/'.$className.'.php';
        return new $className;
    }
}
