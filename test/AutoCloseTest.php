<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2016, Gabriel Zerbib.
 * @version 2.3.1
 * @package FigDice
 *
 * This file is part of FigDice.
 */

use figdice\View;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

class AutoCloseTest extends PHPUnit_Framework_TestCase
{
  public function testScriptTagWithoutContentIsNotForcedAutoClose()
  {
    $template = <<<TEMPLATE
<bob:template xmlns:bob="http://figdice.org">
  <link href="/assets/style.css" rel="stylesheet" />
  <script src="/assets/require.js"></script>
</bob:template>
TEMPLATE;

    $view = new View();
    $view->loadString($template);
    $output = $view->render();

    $expected = <<<EXPECTED
  <link href="/assets/style.css" rel="stylesheet" />
  <script src="/assets/require.js"></script>
EXPECTED;


    $this->assertEquals(trim($expected), trim($output));

    // Now try without xmlns
    $template = <<<TEMPLATE
<fig:template>
  <link href="/assets/style.css" rel="stylesheet" />
  <script src="/assets/require.js"></script>
</fig:template>
TEMPLATE;

    $view = new View();
    $view->loadString($template);
    $output = $view->render();

    $this->assertEquals(trim($expected), trim($output));
  }

  public function testScriptTagWithoutContentInIncludedFile()
  {
    vfsStream::setup('root');

    $template = <<<TEMPLATE
<html xmlns:fig="http://www.figdice.org">
  <link href="/assets/style.css" rel="stylesheet" />
  <fig:include file="inner.html" />
</html>
TEMPLATE;


    vfsStream::newFile('outer.html')
      ->withContent($template)
      ->at(vfsStreamWrapper::getRoot())
      ;

    $template = <<<TEMPLATE
<fig:template>
  <script src="/assets/require.js"></script>
</fig:template>
TEMPLATE;


    $innerVFile = vfsStream::newFile('inner.html');
    $innerVFile->withContent($template)
      ->at(vfsStreamWrapper::getRoot());


    $filename = vfsStream::url('root/outer.html');
    $view = new View();
    $view->loadFile($filename);
    $output = $view->render();

    $expected = <<<EXPECTED
<html>
  <link href="/assets/style.css" rel="stylesheet" />
    <script src="/assets/require.js"></script>

</html>
EXPECTED;


    $this->assertEquals(trim($expected), trim($output));




    // Now test by inverting the script and link, so that the script is no longer
    // the last tag in the template (Bolek's test)
    $template = <<<TEMPLATE
<fig:template>
  <script src="/assets/require.js"></script>
  <link href="/assets/style.css" rel="stylesheet" />
</fig:template>
TEMPLATE;


    $innerVFile->setContent($template);

    $expected = <<<EXPECTED
<html>
  <link href="/assets/style.css" rel="stylesheet" />
    <script src="/assets/require.js"></script>
  <link href="/assets/style.css" rel="stylesheet" />

</html>
EXPECTED;

    $view = new View();
    $view->loadFile($filename);
    $output = $view->render();
    $this->assertEquals(trim($expected), trim($output));


  }
}
