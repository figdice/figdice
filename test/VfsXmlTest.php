<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.5
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
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Unit Test Class for fig inclusions using the Vfs wrapper
 */
class VfsXmlTest extends PHPUnit_Framework_TestCase {


  public function testVfsIncludeSameDir() {
    vfsStream::setup('root');
    
    $template = <<<ENDXML
<xml>
  <fig:include file="sameDir.xml" />
</xml>
ENDXML;
    
    vfsStream::newFile('template.xml')
      ->at(vfsStreamWrapper::getRoot())
      ->withContent($template);

    $template = <<<ENDXML
<included>
  Hello
</included>
ENDXML;

    vfsStream::newFile('sameDir.xml')
    ->at(vfsStreamWrapper::getRoot())
    ->withContent($template);

    $filename = vfsStream::url('root/template.xml');
    $view = new View();
    $view->loadFile($filename);
    $output = $view->render();

    $expected = <<<ENDHTML
<xml>
  <included>
  Hello
</included>
</xml>
ENDHTML;

    $this->assertEquals($expected, $output);
  }

  public function testVfsIncludeSubDir() {
    vfsStream::setup('root');
  
    $template = <<<ENDXML
<xml>
  <fig:cdata file="subDir/someFile.xml" />
</xml>
ENDXML;
  
    vfsStream::newFile('template.xml')
    ->at(vfsStreamWrapper::getRoot())
    ->withContent($template);
  
    $template = <<<ENDXML
<included>
  Hello
</included>
ENDXML;
  
    vfsStream::newFile('someFile.xml')
    ->at( vfsStream::newDirectory('subDir')->at(vfsStreamWrapper::getRoot()) )
    ->withContent($template);
  
    $filename = vfsStream::url('root/template.xml');
    $view = new View();
    $view->loadFile($filename);
    $output = $view->render();
  
    $expected = <<<ENDHTML
<xml>
  <included>
  Hello
</included>
</xml>
ENDHTML;
  
    $this->assertEquals($expected, $output);
  }


  public function testVfsIncludeParentDir() {
    vfsStream::setup('root');
  
    $template = <<<ENDXML
<xml>
  <fig:include file="../someFile.xml" />
</xml>
ENDXML;
  
    vfsStream::newFile('template.xml')
    ->at( vfsStream::newDirectory('subDir')->at(vfsStreamWrapper::getRoot()) )
    ->withContent($template);
  
    $template = <<<ENDXML
<included>
  Hello
</included>
ENDXML;
  
    vfsStream::newFile('someFile.xml')
    ->at(vfsStreamWrapper::getRoot())
    ->withContent($template);
  
    $filename = vfsStream::url('root/subDir/template.xml');
    $view = new View();
    $view->loadFile($filename);
    $output = $view->render();
  
    $expected = <<<ENDHTML
<xml>
  <included>
  Hello
</included>
</xml>
ENDHTML;
  
    $this->assertEquals($expected, $output);
  }


  public function testCachingProducesFile()
  {
      $template = '<fig:include file="sub/included.html"/>';
      $included = '<div></div>';

      vfsStream::setup('root');
      $templateDir = vfsStream::newDirectory('templates')->at(vfsStreamWrapper::getRoot());
      vfsStream::newDirectory('sub')->at($templateDir);

      vfsStream::newFile('template.html')->withContent($template)->at($templateDir);
      vfsStream::newFile('sub/included.html')->withContent($included)->at($templateDir);
      vfsStream::newDirectory('cache')->at(vfsStreamWrapper::getRoot());

      $view = new View();
      $view->setCachePath(vfsStream::url('root/cache'), vfsStream::url('root/templates'));

      $view->loadFile(vfsStream::url('root/templates/template.html'));
      $output = $view->render();

      // It doesn't hurt to check that the include was processed successfully
      $this->assertEquals('<div></div>', $output);

      // Now check that we have these two files:
      //    root/cache/figs/template.html.fig
      //    root/cache/figs/sub/included.html.fig

      $this->assertTrue(file_exists(vfsStream::url('root/cache/figs/template.html.fig')));
      $this->assertTrue(file_exists(vfsStream::url('root/cache/figs/sub/included.html.fig')));

      // Now let's replay the files, off the cache:
      unlink(vfsStream::url('root/templates/template.html'));
      unlink(vfsStream::url('root/templates/sub/included.html'));

      $view = new View();
      // Deliberately omit the cache location, so that the loadFile instruction
      // will search for original source files (and will fail)

      try {
          $view->loadFile(vfsStream::url('root/templates/template.html'));
          // If we reach the following assertion, it means that the source file was
          // not properly removed, and there was a problem in VFS
          $this->assertTrue(false);
      } catch (\figdice\exceptions\FileNotFoundException $ex) {
          // Now, specify the cache location
          $view->setCachePath(vfsStream::url('root/cache'), vfsStream::url('root/templates'));
      }

      $view->loadFile(vfsStream::url('root/templates/template.html'));
      $output = $view->render();

      $this->assertEquals('<div></div>', $output);
  }
}
