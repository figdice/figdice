<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3
 * @package FigDice
 */

namespace figdice\test;

use figdice\View;
use PHPUnit_Framework_TestCase;

class SlotPlugTest extends PHPUnit_Framework_TestCase
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
}
