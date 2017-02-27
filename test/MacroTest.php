<?php

use figdice\View;

class MacroTest extends PHPUnit_Framework_TestCase
{
    public function testMacroInvokedInsideIterationDoesNotSeePosition()
    {
        $view = new View();

        $template =
            '<fig:x>'."\n".
            '  <fig:m fig:macro="myMacro">'."\n".
            '    <fig:t fig:text="currentValue"/>=<fig:t fig:text="position()"/>'."\n".
            '  </fig:m>'."\n".
            ''."\n".
            '  <fig:w fig:walk="/arr">'."\n".
            '    <fig:c fig:call="myMacro" currentValue="."/>'."\n".
            '  </fig:w>'."\n".
            '</fig:x>'."\n";

        $view->loadString($template);

        $view->mount('arr', [1, 2, 3]);
        $rendered = /*trim(*/ $view->render() /*)*/;

        $expected =
            "  \n".
            "\n".
            "          1=0\n".
            "  \n".
            "          2=0\n".
            "  \n".
            "          3=0\n".
            "  \n".
            "  \n"
        ;
        $this->assertEquals($expected, $rendered);
    }
}
