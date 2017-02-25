<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.4
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

use figdice\classes\lexer\Lexer;

/**
 * Unit Test Class for basic Lexer expressions
 */
class ExpressionsTest extends PHPUnit_Framework_TestCase {

  private function lexExpr($expression, array $data = null) {
    $lexer = new Lexer($expression);

    // A Lexer object needs to live inside a View,
    // and be bound to a ViewElementTag instance.
    // They both need to be bound to a File object,
    // which must respond to the getCurrentFile method.

    $view = $this->getMock('\\figdice\\View');
    $viewElement = $this->getMock('\\figdice\\classes\\ViewElementTag', array('getCurrentFile'), array(& $view, 'testtag', 12));

      $context = new \figdice\classes\Context($view);
      $context->tag = $viewElement;

      // Make sure that the passed expression is successfully parsed,
    // before asserting stuff on its evaluation.
    $parseResult = $lexer->parse($context);
    $this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

    // Mock the mounting of root data universe into the view
    $view->expects($this->any())->method('fetchData')->will($this->returnValue($data));
    // Root node
    $view->expects($this->any())->method('getRootNode')->will($this->returnValue($viewElement));


    return $lexer->evaluate($context);
  }



  public function testDivByZeroIsZero()
  {
    $this->assertEquals(0, $this->lexExpr( '39 div 0' ));
  }

  public function testFalseAndShortcircuit()
  {
    $this->assertEquals(false, $this->lexExpr( 'false and 12' ));
  }

  public function testStringConcat()
  {
    $this->assertEquals('a2', $this->lexExpr( "'a' + 2 " ));
  }

  /**
   * This method and the following public property are used as a helper
   * for testObjectAttributesInPath in which we tell the expression engine to
   * access the bean-like property of an object.
   * @return int
   */
  public function getSomeGetterValue()
  {
    return 13;
  }
  public $somePublicProperty = 21;
  public function testObjectAttributesInPath()
  {
    $this->assertEquals($this->getSomeGetterValue(), $this->lexExpr( '/rootObj/someGetterValue', array('rootObj' => $this) ));
    $this->assertEquals($this->somePublicProperty, $this->lexExpr( '/rootObj/somePublicProperty', array('rootObj' => $this) ));
    $this->assertNull($this->lexExpr( '/rootObj/someInexistantProperty', array('rootObj' => $this) ));
  }

  public function testSubkeyOfAScalarValueIsNull()
  {
    $this->assertNull( $this->lexExpr('/rootObj/someGetterValue/subkey', array('rootObj' => $this)));
  }

  public function testModZeroIsZero()
  {
    $this->assertEquals(0, $this->lexExpr('3 mod 0'));
  }

  /**
   * @expectedException figdice\exceptions\LexerSyntaxErrorException
   */
  public function testEmptyArgumentsInFunctionCallRaisesException()
  {
    $this->assertEquals(0, $this->lexExpr('some(,)'));
  }
}
