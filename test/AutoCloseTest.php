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

  public function testScriptTatWithoutContentInIncludedFile()
  {
    vfsStream::setup('root');

    $template = <<<TEMPLATE
<fig:template xmlns:fig="http://www.figdice.org">
  <link href="/assets/style.css" rel="stylesheet" />
  <fig:include file="inner.html" />
</fig:template>
TEMPLATE;


    vfsStream::newFile('outer.html')
      ->at(vfsStreamWrapper::getRoot())
      ->withContent($template);

    $template = <<<TEMPLATE
<fig:template>
  <script src="/assets/require.js"></script>
</fig:template>
TEMPLATE;

    vfsStream::newFile('inner.html')
      ->at(vfsStreamWrapper::getRoot())
      ->withContent($template);


    $filename = vfsStream::url('root/outer.html');
    $view = new View();
    $view->loadFile($filename);
    $output = $view->render();

    $expected = <<<EXPECTED
  <link href="/assets/style.css" rel="stylesheet" />
  <script src="/assets/require.js"></script>
EXPECTED;


    $this->assertEquals(trim($expected), trim($output));

  }
}
