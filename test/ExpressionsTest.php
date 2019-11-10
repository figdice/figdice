<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 */
declare(strict_types=1);

use figdice\exceptions\LexerSyntaxErrorException;
use PHPUnit\Framework\TestCase;
use figdice\classes\lexer\Lexer;

/**
 * Unit Test Class for basic Lexer expressions
 */
class ExpressionsTest extends TestCase {

    /**
     * @param $expression
     * @param array|null $data
     *
     * @return mixed
     * @throws \figdice\exceptions\LexerSyntaxErrorException
     * @throws \figdice\exceptions\LexerUnbalancedParenthesesException
     * @throws \figdice\exceptions\LexerUnexpectedCharException
     */
    private function lexExpr($expression, array $data = null) {
    $lexer = new Lexer($expression);

    // A Lexer object needs to live inside a View,
    // and be bound to a ViewElementTag instance.
    // They both need to be bound to a File object,
    // which must respond to getCurrentFile method.

      $view = $this->createMock(\figdice\View::class);
      $viewElement = $this->createMock(\figdice\classes\ViewElementTag::class);

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

  public function testEmptyArgumentsInFunctionCallRaisesException()
  {
    $this->expectException(LexerSyntaxErrorException::class);
    $this->assertEquals(0, $this->lexExpr('some(,)'));
  }

  public function testDotDotsOutsideIterGiveEmpty()
  {
      $this->assertEquals('', $this->lexExpr('../x'));
  }
}
