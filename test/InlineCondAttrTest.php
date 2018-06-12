<?php

use figdice\View;

class InlineCondAttrTest extends PHPUnit_Framework_TestCase
{
    public function testInlineAttributeWithTrueConditionIsExposed()
    {
        $template = <<<END
            <tag attribute="|true|'x'|" />
END;
        $view = new View();
        $view->loadString($template);
        $output = $view->render();
        $this->assertEquals('<tag attribute="x" />', $output);
    }

    public function testInlineAttributeWithFalseConditionIsHidden()
    {
        $template = <<<END
            <tag attribute="|false|'x'|" />
END;
        $view = new View();
        $view->loadString($template);
        $output = $view->render();
        $this->assertEquals('<tag />', $output);
    }
}
