<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 * http://figdice.org/
 */
declare(strict_types=1);

use figdice\exceptions\RenderingException;
use PHPUnit\Framework\TestCase;

use figdice\Filter;
use figdice\View;

class FilterTest extends TestCase
{
  public function testSpecialHTMLCharacterInFigTextIsNotAltered()
  {
    $view = new View();
    $view->loadString(
      '<test><span fig:text="/data"/></test>'
    );
    $view->mount('data', '<TAG>');
    $this->assertEquals('<test><span><TAG></span></test>', $view->render());
  }

  public function testSpecialHTMLCharacterAfterFilter()
  {
    $view = new View();
    $view->loadString(
      '<test><span fig:text="/data" fig:filter="EscapeHtmlFilter"/></test>'
    );
    $view->mount('data', '<TAG>');
    $this->assertEquals('<test><span>&lt;TAG&gt;</span></test>', $view->render());
  }

  public function testHtmlEntitiesBuiltin()
  {
    $view = new View();
    $view->loadString(
      '<test><span fig:text="htmlentities(/data)"/></test>'
    );
    $view->mount('data', '<TAG>');
    $this->assertEquals('<test><span>&lt;TAG&gt;</span></test>', $view->render());
  }

  public function testClassNotFoundRaisesException()
  {
      $view = new View();
      $view->loadString(
          '<test><span fig:filter="DummyNotFoundFilterClass"/></test>'
      );
      $this->expectException(ReflectionException::class);
      $view->render();
  }

  public function testClassDoesNotImplementFilterRaisesException()
  {
      $view = new View();
      $view->loadString(
          '<test><span fig:filter="DummyFilterClassWhichDoesNotImplementsFilter"/></test>'
      );
      $this->expectException(RenderingException::class);
      $view->render();
  }
    public function testFilterClassIsAutoloadableButIsNotFilterRaisesException()
    {
        $view = new View();
        $view->loadString(
            '<test><span fig:filter="figdice\classes\functions\Function_average"/></test>'
        );
        $this->expectException(RenderingException::class);
        $view->render();
    }
}

class DummyFilterClassWhichDoesNotImplementsFilter
{
    public function transform($buffer)
    {
        return $buffer;
    }
}

class EscapeHtmlFilter implements Filter
{

  /**
   * Operates the transform on the input string,
   * returns the filtered output.
   * This test filter simply performs php's native HTML escapes.
   *
   * @param string $buffer
   * @return string
   */
  public function transform($buffer)
  {
    return htmlspecialchars($buffer);
  }
}
