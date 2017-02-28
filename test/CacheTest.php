<?php

use figdice\View;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

class CacheTest extends PHPUnit_Framework_TestCase
{
    public function testAdhocAndFlagAreCachedAndRestored()
    {
        vfsStream::setup('root');

        $template = <<<ENDTEMPLATE
<html>
    <div class="one {/one/more} three" id="mydiv">
        <fig:m fig:macro="mymacro">
            <title fig:text="param1"></title>
        </fig:m>
        <span fig:call="mymacro" param1="'abc'"/>
    </div>
    <input name="email" fig:void="true">
        <fig:attr name="required" flag="1" />
    </input>
</html>
ENDTEMPLATE;

        vfsStream::newFile('template.html')->withContent($template)->at(vfsStreamWrapper::getRoot());
        $view = new View();
        $view->setCachePath(vfsStream::url('root'));
        $view->mount('one', ['more' => 'two']);
        $view->loadFile(vfsStream::url('root/template.html'));
        $output = $view->render();

        $expected = <<<EXPECTED
<html>
    <div class="one two three" id="mydiv">
        
                    <title>abc</title>
        
    </div>
    <input name="email" required>
</html>
EXPECTED;

        $this->assertEquals($expected, $output);

        // Now remove the original file
        unlink(vfsStream::url('root/template.html'));
        $view = new View();
        $view->setCachePath(vfsStream::url('root'));
        $view->mount('one', ['more' => 'two']);
        $view->loadFile(vfsStream::url('root/template.html'));
        $output = $view->render();

        $this->assertEquals($expected, $output);
    }


    public function testDictionaryAndTransSerializing()
    {
        $template = <<<ENDTEMPLATE
<html>
    <fig:dictionary file="dic.xml" />
    <fig:trans fig:cond="false"/>
    <fig:trans>something</fig:trans>
    <fig:trans fig:cond="true" key="mykey">value</fig:trans>
</html>
ENDTEMPLATE;

        $dic = <<<ENDDIC
<dictionary language="fr">
  <entry key="mykey">ma clé</entry>
  <entry key="something">quelque chose</entry>
</dictionary>
ENDDIC;


        vfsStream::setup('root', null, [
            'cache' => [],
            'i18n' => [
                'fr' => [
                    'dic.xml' => $dic
                ]
            ],
            'template.html' => $template
        ]);

        $view = new View();
        $view->setLanguage('fr');
        $view->setTranslationPath(vfsStream::url('root/i18n'));
        $view->setCachePath(vfsStream::url('root'));
        $view->loadFile(vfsStream::url('root/template.html'));
        $output = $view->render();

        $expected = <<<EXPECTED
<html>
    
    
    quelque chose
    ma clé
</html>
EXPECTED;

        $this->assertEquals($expected, $output);

        // Now remove the original file
        unlink(vfsStream::url('root/template.html'));
        $view = new View();
        $view->setLanguage('fr');
        // No need to specify translation path because all dics are in cache already
        $view->setCachePath(vfsStream::url('root'));
        $view->loadFile(vfsStream::url('root/template.html'));
        $output = $view->render();

        $this->assertEquals($expected, $output);
    }
}
