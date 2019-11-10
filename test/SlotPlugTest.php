<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 */
declare(strict_types=1);

namespace figdice\test;
use PHPUnit\Framework\TestCase;
use figdice\View;
use PHPUnit_Framework_TestCase;

class SlotPlugTest extends TestCase
{
  public function testPlugExecutesInLocalContext()
  {
    $template = <<<ENDTEMPLATE

<fig:template>
  <!-- define a slot -->
  <slot fig:slot="myslot">Default Slot Content</slot>

  <!-- create some contextual data -->
  <fig:mount target="someData" value="1" />

  <!-- execute the plug in the current context -->
  <title fig:mute="true" fig:plug="myslot" fig:text="/someData"/>

  <!-- modify the data that the plug refers to.
       Prior to version 2.3, the plug contents would render using the ending global context,
       but since 2.3 the plug is executed in its local context. -->
  <fig:mount target="someData" value="2" />
</fig:template>

ENDTEMPLATE;

    $view = new View([View::GLOBAL_PLUGS]);
    $view->loadString(trim($template));

    $rendered = $view->render();
    $this->assertEquals(2, trim($rendered));

    $view = new View();
    $view->loadString(trim($template));

    $rendered = $view->render();
    $this->assertEquals(1, trim($rendered));
  }

  public function testPlugInIncludedFileUsesParentViewOption()
  {
    // New behaviour for plugs: rendered in local context.
    $view = new View();
    $view->loadFile(__DIR__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'slot-in-parent.xml');
    $output = trim($view->render());

    $this->assertEquals(11, $output);

    // Legacy behaviour: rendered in final global context
    // What we are trying to test is: the plug is defined in an included template, and yet its rendering process
    // checks properly that the top view has the GLOBAL_PLUGS defined.
    $view = new View([View::GLOBAL_PLUGS]);
    $view->loadFile(__DIR__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'slot-in-parent.xml');
    $output = trim($view->render());

    $this->assertEquals(12, $output);
  }
}
