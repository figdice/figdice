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
use figdice\classes\ViewElementTag;
use \org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use \figdice\classes\Dictionary;

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

    // We will need to access the resolved dictionaries in their post-rendering state.
      // The dictionaries are accessible through the Context object, which the view instantiates at render time,
      // as a local variable. There is no access to the Context.
      // We prepare a man-in-the-middle spoof ViewElementTag root node for the view.
    $this->view->parse();
    // After the parsing, the view has its root node. We will facade it
      // with an object that knows to capture the Context instance it will receive when asked to render.
      $rootNode = new ContextCheaterViewElementTag($this->view->getRootNode());
      $reflectorView = new ReflectionClass($this->view);
      $reflectorProp = $reflectorView->getProperty('rootNode');
      $reflectorProp->setAccessible(true);
      $reflectorProp->setValue($this->view, $rootNode);


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
              $dict = $rootNode->context->getDictionary('inParent'))
      );
      $cacheProp = $reflector->getProperty('cache');
      $cacheProp->setAccessible(true);
      $cache = $cacheProp->getValue($dict);
      // And I know for a fact that it translates twice the "key1" key.
      $this->assertEquals('key1-parent', $cache['key1']);

  }

  /**
   * @expectedException \figdice\exceptions\FileNotFoundException
   */
  public function testDictionaryNotFoundException()
  {
    $view = new View();
    $view->loadString(
      '<fig:template>'.
        '<fig:dictionary file="file-not-found.xml" source="en"/>'.
      '</fig:template>'
    );
    $view->setLanguage('fr');

    $view->render();
    $this->assertTrue(false);
  }

  public function testDictionaryCompiler()
  {
    vfsStream::setup('root');

    $dic = <<<DIC
<fig:dictionary xmlns:fig="http://figdice.org/" language="fr">
  <entry key="foo">bar</entry>
  <entry key="David">Bowie</entry>
</fig:dictionary>
DIC;

    $vDicFile = vfsStream::newFile('en/dic.xml')
      ->at(vfsStreamWrapper::getRoot());

    $vDicFile->withContent($dic);

    $target = vfsStream::url('root/Dictionary/en/dic.xml.php');
    $compilationResult = Dictionary::compile(vfsStream::url($vDicFile->path()), $target);

    $this->assertTrue($compilationResult);
    $this->assertFileExists($target);

    $this->assertEquals('a:2:{s:3:"foo";s:3:"bar";s:5:"David";s:5:"Bowie";}', file_get_contents($target));


    // Test the loading of a pre-compiled dictionary:

    $view = new View();
    // Indicate where the source dictionary is located
    $view->setTranslationPath(vfsStream::url('root'));
    // Indicate where the compiled dictionaries are located.
    $view->setCachePath(vfsStream::url('root'));

    $viewString = <<<ENDTEMPLATE
<fig:template>
  <fig:dictionary file="dic.xml" />
  <fig:trans key="David"/>
</fig:template>
ENDTEMPLATE;

    $view->loadString($viewString);
    $view->setLanguage('en');
    $this->assertEquals('Bowie', trim( $view->render() ));

  }

  public function testWrongDicNameRaisesError()
  {
    $view = new View();
    $str = <<<ENDTEMPLATE
<fig:template>
  <fig:dictionary file="dic.xml" source="fr" name="mydic" />
  <fig:trans dict="anotherdic" key="somekey"/>
</fig:template>
ENDTEMPLATE;

    $view->loadString($str);
    $view->setLanguage('en');

    vfsStream::setup('root');
    $vDir = vfsStream::newDirectory('en')->at(vfsStreamWrapper::getRoot());
    $vDicFile = vfsStream::newFile('dic.xml')->at($vDir);

    $dicString = <<<ENDDIC
<fig:dictionary xmlns:fig="http://figdice.org">
</fig:dictionary>
ENDDIC;

    $vDicFile->withContent($dicString);

    $view->setTranslationPath(vfsStream::url('root'));

    try {
      $view->render();
      $this->assertTrue(false);
    } catch (\figdice\exceptions\DictionaryNotFoundException $ex) {
      $this->assertEquals('anotherdic', $ex->getDictionaryName());
    }
  }

  /**
   * @expectedException \figdice\exceptions\DictionaryEntryNotFoundException
   */
  public function testWrongKeyRaisesError()
  {
    $view = new View();
    $str = <<<ENDTEMPLATE
<fig:template>
  <fig:dictionary file="dic.xml" source="fr" name="mydic" />
  <fig:trans dict="mydic" key="somekey"/>
</fig:template>
ENDTEMPLATE;

    $view->loadString($str);
    $view->setLanguage('en');

    vfsStream::setup('root');
    $vDir = vfsStream::newDirectory('en')->at(vfsStreamWrapper::getRoot());
    $vDicFile = vfsStream::newFile('dic.xml')->at($vDir);

    $dicString = <<<ENDDIC
<fig:dictionary xmlns:fig="http://figdice.org">
</fig:dictionary>
ENDDIC;

    $vDicFile->withContent($dicString);

    $view->setTranslationPath(vfsStream::url('root'));
    $view->render();
  }

  /**
   * @expectedException \figdice\exceptions\DictionaryEntryNotFoundException
   */
  public function testAnonTransWithoutLoadedDicRaisesError()
  {
    $view = new View();
    $str = <<<ENDTEMPLATE
<fig:template>
  <fig:trans key="somekey"/>
</fig:template>
ENDTEMPLATE;

    $view->loadString($str);
    $view->setLanguage('en');

    vfsStream::setup('root');

    $view->setTranslationPath(vfsStream::url('root'));
    $view->render();
  }
}

/**
 * This class is used as a spoof for Root Node in View.
 * It captures the instance of Context that it is passed, when asked to render.
 */
class ContextCheaterViewElementTag extends ViewElementTag
{
    public function __construct(ViewElementTag $realRootNode)
    {
        parent::__construct($realRootNode->getTagName(), $realRootNode->xmlLineNumber);
        $this->realRootNode = $realRootNode;
    }

    /** @var \figdice\classes\Context */
    public $context;
    /** @var ViewElementTag */
    private $realRootNode;

    public function render(\figdice\classes\Context $context)
    {
        $this->context = $context;
        return $this->realRootNode->render($context);
    }
}
