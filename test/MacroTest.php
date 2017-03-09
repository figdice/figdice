<?php

use figdice\View;

class MacroTest extends PHPUnit_Framework_TestCase
{
    public function testMacroInvokedInsideIterationDoesNotSeePosition()
    {
        $view = new View();

        $template = <<<ENDTEMPLATE
<fig:x>
  <fig:m fig:macro="myMacro">
    <fig:t fig:text="currentValue"/>=<fig:t fig:text="position()"/>
  </fig:m>

  <fig:w fig:walk="/arr">
    <fig:c fig:call="myMacro" currentValue="."/>
  </fig:w>
</fig:x>
ENDTEMPLATE;


        $view->loadString($template);

        $view->mount('arr', [1, 2, 3]);
        $rendered = $view->render();

        $expected = <<<EXPECTED
  

          1=0
  
  

          2=0
  
  

          3=0
  
  

EXPECTED;


        $this->assertEquals($expected, $rendered);
    }
}
