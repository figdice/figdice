<?php

use figdice\View;

class WalkTest extends PHPUnit_Framework_TestCase
{
    public function testWalkIndexedArray()
    {
        $source = <<<ENDXML
<fig:x fig:walk="/data"><fig:x fig:text="."/>,</fig:x>
ENDXML;

        $view = new View();
        $view->loadString($source);
        $view->mount('data', array('a', 'b', 'c'));
        $this->assertEquals("a,b,c,", $view->render());
    }


    public function testWalkRespectsIndentation()
    {
        $template =
            '<fig:w fig:walk="/arr">'."\n".
            '  X'."\n".
            '</fig:w>'."\n"
        ;

        $expected =
            '  X'."\n".
            '  X'."\n".
            '  X'."\n"
        ;

        $view = new View();
        $view->loadString($template);

        $view->mount('arr', [1, 2, 3]);
        $actual = $view->render();
        $this->assertEquals($expected, $actual);
    }
}
