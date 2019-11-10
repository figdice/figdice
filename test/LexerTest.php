<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.3
 * @package FigDice
 *
 * This file is part of FigDice.
 */
declare(strict_types=1);

use figdice\exceptions\FunctionNotFoundException;
use figdice\exceptions\LexerArrayToStringConversionException;
use figdice\exceptions\LexerSyntaxErrorException;
use figdice\exceptions\LexerUnbalancedParenthesesException;
use figdice\exceptions\LexerUnexpectedCharException;
use PHPUnit\Framework\TestCase;
use figdice\classes\lexer\Lexer;
use figdice\View;

/**
 * Unit Test Class for basic Lexer expressions
 */
class LexerTest extends TestCase {

  private function lexParse($expression) {
    $lexer = new Lexer($expression);
    $context = new \figdice\classes\Context(new View());

    $parseResult = $lexer->parse($context);
    $this->assertTrue($parseResult);

    return $lexer->getTree();
  }

  private function lexExpr($expression, array $data = null) {
    $lexer = new Lexer($expression);

    // A Lexer object needs to live inside a View,
    // and be bound to a ViewElementTag instance.
    // They both need to be bound to a File object,
    // which must respond to the getCurrentFile method.

    $view = $this->createMock(View::class);
      $viewElement = $this->createMock(\figdice\classes\ViewElementTag::class);

    $context = new \figdice\classes\Context($view);
    $context->tag = $viewElement;

    // Make sure that the passed expression is successfully parsed,
    // before asserting stuff on its evaluation.
    $parseResult = $lexer->parse($context);
    $this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

    // Mock the mounting of root data universe into the view
    // CAUTION: you cannot use relative paths in this test case,
    // because relative path resolution involves full View class.
    // Here we can only have top-level symbols in our mock.
    $view->expects($this->any())->method('fetchData')->will($this->returnValue($data));
    // Root Node
    $view->expects($this->any())->method('getRootNode')->will($this->returnValue($viewElement));

    return $lexer->evaluate($context);
  }


  public function testTrue()
  {
    $this->assertTrue($this->lexExpr( 'true' ));
  }

  public function testEmptyExpressionIsFalse()
  {
    $this->assertFalse($this->lexExpr( '' ));
    $this->assertFalse($this->lexExpr( '   ' ));
  }

  public function testParseErrorThrowsException() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr( '2 == true=' );
  }

  public function testMissingOperatorException()
  {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr( '2 5' );
  }

  public function testMissingOperandException()
  {
    $this->expectException(LexerSyntaxErrorException::class);
    $this->lexExpr( '2 div' );
  }

  public function testTwoConsecutiveBinOperatorsException()
  {
    $this->expectException(LexerSyntaxErrorException::class);
    $this->lexExpr( '* div' );
  }

  public function test1Plus1()
  {
    $this->assertEquals(2, $this->lexExpr( '1+1' ));
  }

  public function testMul()
  {
    $this->assertEquals(28, $this->lexExpr( '4*7' ));
  }

  public function testDiv()
  {
    $this->assertEquals(13, $this->lexExpr( '39 div 3' ));
  }

  public function testDotAloneInUniverseIsNull() {
    $this->assertNull($this->lexExpr(' . '));
  }

  public function testDotInvalidlyDelimitedRaisesError() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr(' .x ');
  }

  public function testArithmeticPriority()
  {
    $this->assertEquals(15, $this->lexExpr( '39 div 3 + 2' ));
  }

  public function testParentheses()
  {
    $this->assertEquals(13, $this->lexExpr( '(35 + 4) div (4 - 1)' ));
  }

  public function testLogicalAnd()
  {
    $this->assertEquals(true, $this->lexExpr( '(35 + 4) and true' ));
  }
  public function testLogicalNot()
  {
    $this->assertTrue($this->lexExpr( 'not(false)' ));
  }
  public function testGreaterThan()
  {
    $this->assertTrue($this->lexExpr( '8 gt 5' ));
  }

  public function testLogicalOr()
  {
    $this->assertFalse($this->lexExpr( "false or 0 or '' " ));
    $this->assertTrue($this->lexExpr( "false or 0 or 'a' " ));
    $this->assertTrue($this->lexExpr( "true or 0 or '' " ));
    $this->assertTrue($this->lexExpr( "false or 12 or '' " ));
  }

  public function testComparatorEquals()
  {
    $this->assertEquals(true, $this->lexExpr( '(35 + 4) == 39' ));
  }

  public function testComparatorNotEquals()
  {
    $this->assertEquals(true, $this->lexExpr( '4 != 5' ));
  }

  public function testExplicitPositiveNumber()
  {
    $this->assertEquals(13, $this->lexExpr(' +12 +1'));
  }
  public function testNegativeLiteral()
  {
    $this->assertEquals(-3, $this->lexExpr(' - 3'));
    $this->assertEquals(-2.86, $this->lexExpr(' - 3 + 0.14'));
    $this->assertEquals(-3.14, $this->lexExpr(' - 3 - 0.14'));
  }
  public function testAlphanumCompare()
  {
    $this->assertTrue($this->lexExpr(" 'abc' == 'abc' "));
  }
  public function testAlphanumIsCaseSensitive()
  {
    $this->assertTrue($this->lexExpr(" 'abc' != 'ABC' "));
  }
  public function testEmptyStringIsNotZero()
  {
    $this->assertFalse($this->lexExpr(" '' == 0 "));
  }
  public function testChainingComparisons()
  {
    $this->assertEquals(true, $this->lexExpr( '(35 + 4) == 39 and 3 == (4 - 1)' ));
  }


  public function testDecimalCompare()
  {
    $this->assertTrue($this->lexExpr(" 3.14 == (3 + 0.14) "));
  }
  public function testPriorityOfArithmeticOverComparison()
  {
    $this->assertTrue($this->lexExpr(  ' 3 == 2 + 1' ));
  }

  public function testDecimalMulInt()
  {
    $this->assertEquals(19.47*2, $this->lexExpr(" 19.47 * 2 "));
  }
  public function testDecimalPlusInt()
  {
    $this->assertEquals(19.47+2, $this->lexExpr(" 19.47 + 2 "));
  }

  public function testParserWithUndefinedFunction()
  {
    $this->expectException(FunctionNotFoundException::class);
    $this->lexExpr( ' somefunc(12) ' );
  }

  public function testDecimalLiteralIsAllowedToStartWithDot()
  {
    $this->assertEquals(.14, $this->lexExpr('.14'));
  }

  public function testTwoSymbolsOneAfterTheOtherSyntaxError() {
    $this->expectException(LexerSyntaxErrorException::class);
    $this->lexExpr(' symbol1 symbol2');
  }
  public function testDotDotPathParsing() {

    $tree = $this->lexParse('../nothing/here');

    $this->assertInstanceOf('figdice\classes\lexer\TokenPath', $tree);
    $reflector = new ReflectionClass($tree);
    $prop = $reflector->getProperty('path');
    $prop->setAccessible(true);
    $path = $prop->getValue($tree);

    $this->assertInstanceOf('figdice\classes\lexer\PathElementParent', $path[0]);

  }
  public function testDotDotAnyCharError() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr('..invalid');
  }
  public function testIllegalParenInPath() {
    $this->expectException(LexerSyntaxErrorException::class);
    $this->lexExpr(" a/b/c(12)");
  }
  public function testDynamicPathParsing() {
    //Null because the said path values don't exist in Universe.
    $this->assertNull($this->lexExpr('/a/b/[c]'));
  }
  public function testUnclosedDynamicPathError() {
    $this->expectException(LexerSyntaxErrorException::class);
    $this->lexExpr('/a/b/[c');
  }
  public function testUnclosedFunctionThrowsException () {
    $this->expectException(LexerUnbalancedParenthesesException::class);
    $this->assertFalse($this->lexExpr( "substr('abcd', 2" ) );
  }

  public function testFuncArgIsOperator()
  {
    $tree = $this->lexParse('substr(1 + 3, 4)');

    $this->assertInstanceOf('\figdice\classes\lexer\TokenFunction', $tree);

    $reflector = new ReflectionClass(get_class($tree));
    $property = $reflector->getProperty('operands');
    $property->setAccessible(true);

    $operands = $property->getValue($tree);
    $this->assertEquals(2, count($operands));
    $this->assertInstanceOf('\figdice\classes\lexer\TokenPlusMinus', $operands[0]);
    $this->assertInstanceOf('\figdice\classes\lexer\TokenLiteral', $operands[1]);
  }

  public function testFuncArgIsFunc()
  {
    $tree = $this->lexParse('substr( myfunc(12), 4)');

    $this->assertInstanceOf('\figdice\classes\lexer\TokenFunction', $tree);

    $reflector = new ReflectionClass(get_class($tree));
    $property = $reflector->getProperty('operands');
    $property->setAccessible(true);

    $operands = $property->getValue($tree);
    $this->assertEquals(2, count($operands));
    $this->assertInstanceOf('\figdice\classes\lexer\TokenFunction', $operands[0]);
    $this->assertInstanceOf('\figdice\classes\lexer\TokenLiteral', $operands[1]);
  }

  public function testMisuseOfLParen() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->assertTrue( $this->lexExpr(' 3 ( 7') );
  }

  public function testIndexedArraySubpath()
  {
    $myArr = array(9, 11, 5);
    $myIndex = 1;
    $this->assertEquals( $myArr[$myIndex], $this->lexExpr('/myArr/[/myIndex]', array('myIndex' => $myIndex, 'myArr' => $myArr)) );

    // The following two are equivalent: it doesn't make much sense to sub-evaluate [2],
    // which worths the same as the literal 2, but it isn't illegal either.
    // Of course, the real purpose of sub-path components, is when you don't know in advance
    // the key or index of the value that you need to fetch.
    $this->assertEquals( $myArr[2], $this->lexExpr('/myArr/2', array('myArr' => $myArr)) );
    $this->assertEquals( $myArr[2], $this->lexExpr('/myArr/[2]', array('myArr' => $myArr)) );
  }

  public function testMod()
  {
    $this->assertEquals( 13, $this->lexExpr("363 mod 50") );
  }

  public function testArrayToStringException() {
    $this->expectException(LexerArrayToStringConversionException::class);
    $this->lexExpr('aaa == 12', ['aaa' => [1, 2, 3]]);
  }

  public function testOpeningParenForFuncAndEOI() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr('openFunc(');
  }

  public function testParenInsideFunc()
  {
    //TODO: what should really be done is: register myFunc and check the result.
    $this->expectException(FunctionNotFoundException::class);
    $this->lexExpr('myfunc((1))');
  }

  public function testPathAndComparatorWithoutSpace()
  {
    $this->assertTrue($this->lexExpr('/var/www==13', ['var'=>['www'=>13]]));
  }
  public function testPathAndPlusWithoutSpace()
  {
    $this->assertTrue($this->lexExpr('/var/www+13 == 26', ['var'=>['www'=>13]]));
  }

  public function testCommaWithoutFuncException() {
    $this->expectException(LexerUnbalancedParenthesesException::class);
    $this->lexExpr('1, 2, 3');
  }

  public function testDynamicSubpathWithOpertion()
  {
    $this->assertEquals(12, $this->lexExpr('/var/[/i + 1]', ['i' => 1, 'var' => ['2' => 12]]));
  }

  public function testIllegalCharacterAfterSymbolException() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr('illegal $');
  }

  public function testSymbolAsNotLastArg()
  {
    $tree = $this->lexParse('func(symb, 12)');
    //Root of tree is a Function
    $this->assertInstanceOf('\figdice\classes\lexer\TokenFunction', $tree);

    $reflector = new ReflectionClass($tree);
    $property = $reflector->getProperty('operands');
    $property->setAccessible(true);
    $operands = $property->getValue($tree);

    // that has 2 operands
    $this->assertEquals(2, count($operands));
    // first one is symbol,
    $this->assertInstanceOf('figdice\classes\lexer\TokenSymbol', $operands[0]);
    // second is literal
    $this->assertInstanceOf('figdice\classes\lexer\TokenLiteral', $operands[1]);

  }
  public function testDecimalAsNotLastArg()
  {
    $tree = $this->lexParse('func(3.25, 12)');
    //Root of tree is a Function
    $this->assertInstanceOf('\figdice\classes\lexer\TokenFunction', $tree);

    $reflector = new ReflectionClass($tree);
    $property = $reflector->getProperty('operands');
    $property->setAccessible(true);
    $operands = $property->getValue($tree);

    // that has 2 operands
    $this->assertEquals(2, count($operands));
    // first one is decimal 3.25,
    $this->assertInstanceOf('figdice\classes\lexer\TokenLiteral', $operands[0]);
    $this->assertEquals(3.25, $operands[0]->value);
    // second is literal
    $this->assertInstanceOf('figdice\classes\lexer\TokenLiteral', $operands[1]);

  }

  public function testSymbolCannotStartByNumber() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr( '123abc' );
  }
  public function testSymbolCannotStartByDecimal() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr( '12.3abc' );
  }
  public function testDecimalFollowedByInvalid() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr( '12.3:' );
  }


  public function testSymbolPlusLiteralOk()
  {
    // Closed symbol (with space)
    $tree = $this->lexParse('symb + 12');
    //Root of tree is a Function
    $this->assertInstanceOf('\figdice\classes\lexer\TokenPlusMinus', $tree);

    $reflector = new ReflectionClass($tree);
    $property = $reflector->getProperty('operands');
    $property->setAccessible(true);
    $operands = $property->getValue($tree);

    // that has 2 operands
    $this->assertEquals(2, count($operands));
    // first one is symbol,
    $this->assertInstanceOf('figdice\classes\lexer\TokenSymbol', $operands[0]);
    // second is literal
    $this->assertInstanceOf('figdice\classes\lexer\TokenLiteral', $operands[1]);



    // Unclosed symbol (no space before the +)
    $tree2 = $this->lexParse('symb+ 12');



    //Root of tree is a Function
    $this->assertInstanceOf('\figdice\classes\lexer\TokenPlusMinus', $tree2);

    $reflector = new ReflectionClass($tree2);
    $property = $reflector->getProperty('operands');
    $property->setAccessible(true);
    $operands = $property->getValue($tree2);

    // that has 2 operands
    $this->assertEquals(2, count($operands));
    // first one is symbol,
    $this->assertInstanceOf('figdice\classes\lexer\TokenSymbol', $operands[0]);
    // second is literal
    $this->assertInstanceOf('figdice\classes\lexer\TokenLiteral', $operands[1]);


  }

  public function testCompareEqualsToEmptyIsFalse()
  {
    $this->assertFalse( $this->lexExpr('/a == /b', ['a' => 12]));
    $this->assertFalse( $this->lexExpr('/a == /b', ['b' => 12]));
  }
  public function testCompareEqualsFloats()
  {
    $this->assertTrue( $this->lexExpr('/a == /b', ['a' => 12.5, 'b' => 12.5]));
  }

  public function testCompareFloatsNotEqual()
  {
    $this->assertTrue( $this->lexExpr('/a != /b', ['a' => floatval(12.5), 'b' => floatval(11.5)]));
  }

  public function testCommaInEmptyRaisesError()
  {
      $this->expectException(LexerUnbalancedParenthesesException::class);
      $this->lexExpr(',');
  }

  public function testClosingParenInEmptyRaisesError() {
    $this->expectException(LexerSyntaxErrorException::class);
    $this->lexExpr(' ) ');
  }

  public function testGarbageInEmptyRaisesError() {
    $this->expectException(LexerUnexpectedCharException::class);
    $this->lexExpr(' : ');
  }

    public function testTwoManyDotDotsAreUnimplemented()
    {
        $view = new View();
        $template =
            '<fig:w fig:walk="/arr">'."\n".
            '  <fig:t fig:text="../../../x"/>'."\n".
            '</fig:w>'
        ;
        $view->loadString($template);
        $view->mount('arr', [1, 2, [3, 'y' => 4], 'x' => 7]);

        $this->expectException(LexerUnexpectedCharException::class);
        $actual = $view->render();
        $this->assertEquals('', $actual);

        // I wish it were already fine to navigate through the parent iterations in
        // nested loops, but for now I wrote only the immediate parent loop's access,
        // with simply "../something".
        // If anyone wants to contribute...
    }

}
