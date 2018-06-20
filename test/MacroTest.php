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

    public function testCallAMacroThatDoesNotExistsGivesEmpty()
    {
        $template = <<<TEMPLATE
<fig>
  <span fig:call="macroNotFound" param1="2 + 2">
    discarded content
  </span>
</fig>
TEMPLATE;

        $expected = <<<EXPECTED
<fig>
  
</fig>
EXPECTED;

        $view = new View();
        $view->loadString($template);
        $this->assertEquals($expected, $view->render());
    }
}
