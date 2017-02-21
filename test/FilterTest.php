<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
 * @package FigDice
 *
 * This file is part of FigDice.
 * http://figdice.org/
 */

use figdice\Filter;
use figdice\View;

class FilterTest extends PHPUnit_Framework_TestCase
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

    /**
     * @expectedException \ReflectionException
     */
  public function testClassNotFoundRaisesException()
  {
      $view = new View();
      $view->loadString(
          '<test><span fig:filter="DummyNotFoundFilterClass"/></test>'
      );
      $view->render();
      $this->assertTrue(false);
  }

    /**
     * @expectedException \figdice\exceptions\RenderingException
     */
  public function testClassDoesNotImplementFilterRaisesException()
  {
      $view = new View();
      $view->loadString(
          '<test><span fig:filter="DummyFilterClassWhichDoesNotImplementsFilter"/></test>'
      );
      $view->render();
      $this->assertTrue(false);
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
