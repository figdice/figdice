<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use figdice\classes\ViewElement;
use figdice\View;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use figdice\classes\Dictionary;
use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\DictionaryEntryNotFoundException;

/**
 * Unit Test Class for fig i18n
 */
class DictionaryTest extends TestCase {

    /** @var  View */
    protected $view;

    protected function setUp(): void {
        $this->view = new View();
    }
    protected function tearDown(): void {
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

    public function testDictionaryNotFoundException()
    {
        $view = new View();
        $view->loadString(
            '<fig:template>'.
            '<fig:dictionary file="file-not-found.xml" source="en"/>'.
            '</fig:template>'
        );
        $view->setLanguage('fr');

	$this->expectException(FileNotFoundException::class);
	$view->render();
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
            ->withContent($dic)
            ->at(vfsStreamWrapper::getRoot());

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

        $dicString = <<<ENDDIC
<fig:dictionary xmlns:fig="http://figdice.org">
</fig:dictionary>
ENDDIC;

        vfsStream::newFile('dic.xml')->withContent($dicString)->at($vDir);

        $view->setTranslationPath(vfsStream::url('root'));

        try {
            $view->render();
            $this->assertTrue(false);
        } catch (\figdice\exceptions\DictionaryNotFoundException $ex) {
            $this->assertEquals('anotherdic', $ex->getDictionaryName());
        }
    }

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

        $dicString = <<<ENDDIC
<fig:dictionary xmlns:fig="http://figdice.org">
</fig:dictionary>
ENDDIC;

        vfsStream::newFile('dic.xml')->withContent($dicString)->at($vDir);

	$view->setTranslationPath(vfsStream::url('root'));
	$this->expectException(DictionaryEntryNotFoundException::class);
        $view->render();
    }

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
	$this->expectException(DictionaryEntryNotFoundException::class);
        $view->render();
    }

    public function testAnonymousDictionaryLoadedInIncludedFileIsNotAvailToParent()
    {
        $templateOuter = <<<ENDTEMPLATE
<fig:template>
    <fig:include file="inner.html"/>
    <fig:trans key="testkey1"/>
</fig:template>
ENDTEMPLATE;


        $templateInner = <<<ENDTEMPLATE
<fig:template>
    <fig:dictionary file="dic.xml"/>
    <fig:trans key="testkey2"/>
</fig:template>
ENDTEMPLATE;


        $dictionary = <<<ENDDICTIONARY
<fig:dictionary xmlns:fig="figdice.org" language="en">
    <entry key="testkey1">testvalue1</entry>
    <entry key="testkey2">testvalue2</entry>
</fig:dictionary>
ENDDICTIONARY;


        vfsStream::setup('root', null, [
            'outer.html' => $templateOuter,
            'inner.html' => $templateInner,
            'i18n' => [
                'en' => [
                    'dic.xml' => $dictionary
                ]
            ]
        ]);

        $view = new View();
        $view->setTranslationPath(vfsStream::url('root/i18n'));
        $view->setLanguage('en');
	$view->loadFile(vfsStream::url('root/outer.html'));
	$this->expectException(DictionaryEntryNotFoundException::class);
        $view->render();
    }


    public function testNamedDictionaryLoadedInIncludedFileIsAvailToParent()
    {
        $templateOuter = <<<ENDTEMPLATE
<fig:template>
    <fig:include file="inner.html"/>
    <fig:trans key="testkey1" dict="namedDic"/>
</fig:template>
ENDTEMPLATE;


        $templateInner = <<<ENDTEMPLATE
<fig:template>
    <fig:dictionary file="dic.xml" name="namedDic" />
    <fig:trans key="testkey2" dict="namedDic"/>
</fig:template>
ENDTEMPLATE;


        $dictionary = <<<ENDDICTIONARY
<fig:dictionary xmlns:fig="figdice.org" language="en">
    <entry key="testkey1">testvalue1</entry>
    <entry key="testkey2">testvalue2</entry>
</fig:dictionary>
ENDDICTIONARY;


        vfsStream::setup('root', null, [
            'outer.html' => $templateOuter,
            'inner.html' => $templateInner,
            'i18n' => [
                'en' => [
                    'dic.xml' => $dictionary
                ]
            ]
        ]);

        $view = new View();
        $view->setTranslationPath(vfsStream::url('root/i18n'));
        $view->setLanguage('en');
        $view->loadFile(vfsStream::url('root/outer.html'));
        $output = $view->render();

        $expected = <<<EXPECTED
        
    testvalue2

    testvalue1

EXPECTED;

        $this->assertEquals($expected, $output);
    }

}

/**
 * This class is used as a spoof for Root Node in View.
 * It captures the instance of Context that it is passed, when asked to render.
 */
class ContextCheaterViewElementTag extends ViewElement
{
    public function __construct(ViewElement $realRootNode)
    {
        parent::__construct();
        $this->realRootNode = $realRootNode;
    }

    /** @var \figdice\classes\Context */
    public $context;
    /** @var ViewElement */
    private $realRootNode;

    public function render(\figdice\classes\Context $context)
    {
        $this->context = $context;
        return $this->realRootNode->render($context);
    }

    public function appendCDataSibling($cdata)
    {
    }
}
