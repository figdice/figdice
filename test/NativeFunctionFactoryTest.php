<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 */
declare(strict_types=1);

use figdice\exceptions\FunctionCallException;
use figdice\exceptions\FunctionNotFoundException;
use PHPUnit\Framework\TestCase;
use figdice\classes\lexer\Lexer;
use figdice\View;
use figdice\classes\ViewElementTag;

/**
 * Unit Test Class for basic Lexer expressions
 */
class NativeFunctionFactoryTest extends TestCase
{

    /**
     * @var View
     */
    private $view;

    protected function setUp(): void
    {
        $this->view = new View();
    }

    private function lexExpr($expression)
    {
        $lexer = new Lexer($expression);

        // A Lexer object needs to live inside a View,
        // and be bound to a ViewElementTag instance.
        // They both need to be bound to a File object,
        // which must respond to the getCurrentFile method.


        // In this test, we need a real View object, because
        // it embeds a real NativeFunctionFactory instance.
        $view = $this->view;

        // Make sure that the passed expression is successfully parsed,
        // before asserting stuff on its evaluation.
        $context = new \figdice\classes\Context($view);
        $context->pushTag(new ViewElementTag('test', 0));
        $parseResult = $lexer->parse($context);
        $this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

        return $lexer->evaluate($context);
    }


    public function testFunctionGETdoesNotProvideAnythingInCLI()
    {
        $this->assertEquals(0, $this->lexExpr("GET('x')"));
    }

    public function testFunctionMockGETinCLI()
    {
        $_GET['foo'] = 'bar';
        $this->assertEquals('bar', $this->lexExpr("GET('foo')"));
    }

    public function testIfFuncForYesOperand()
    {
        $this->assertEquals('yes', $this->lexExpr(" if(true, 'yes', 'no')  "));
    }

    public function testIfFuncForNoOperand()
    {
        $this->assertEquals('no', $this->lexExpr(" if(false, 'yes', 'no')  "));
    }

    public function testIfFuncWithMissingArgsThrowsException()
    {
        $this->expectException(FunctionCallException::class);
        $this->lexExpr(" if(1)  ");
    }

    public function testIfFuncWithOneFalseArgDefaultsToEmpty()
    {
        $this->assertEmpty($this->lexExpr(" if(1 == 2, 3)  "));
    }

    public function testIfFuncWithOneTrueArgReturns()
    {
        $this->assertEquals(3, $this->lexExpr(" if(2 == 2, 3)  "));
    }

    public function testCountOfEmptyIsZero()
    {
        $this->assertEquals(0, $this->lexExpr(' count(/dummy/value) '));
    }

    public function testCountOfLiteralIsZero()
    {
        $this->assertEquals(0, $this->lexExpr(' count( 12 ) '));
    }

    public function testCountOfArrayIsCount()
    {
        $this->view->mount('myArray', array(1, 2, 3));
        $this->assertEquals(3, $this->lexExpr(' count( /myArray ) '));
    }

    public function testCountIfNotZeroIsCount() {
        $this->view->mount('myArray', ['x', 'y', 'z']);
        $this->assertEquals(3, $this->lexExpr(' countif( /myArray ) '));
    }

    public function testCountIfZeroIsEmpty() {
        $this->view->mount('myArray', [ ]);
        $this->assertEquals('', $this->lexExpr(' countif( /myArray ) '));
    }

    public function testCountIfNonArrayIsEmpty() {
        $this->view->mount('myString', 'hello');
        $this->assertEquals('', $this->lexExpr(' countif( /myString ) '));
    }

    public function testCountIfFuncWithMissingArgsThrowsException() {
        $this->expectException(FunctionCallException::class);
        $this->lexExpr(" countif() ");
    }

    public function testCountWithoutArgInsideIteration()
    {
        $data = ['a', 'b', 'c'];
        $template = '<fig:w fig:walk="/data" fig:text="count()"/>';

        $view = new View();
        $view->loadString($template);
        $view->mount('data', $data);
        $output = $view->render();
        $this->assertEquals('333', $output);
    }

    public function testSumOfArrayIsOk()
    {
        $this->view->mount('myArray', array(1, 2, 3));
        $this->assertEquals(6, $this->lexExpr(' sum( /myArray ) '));
    }

    /**
     * If you have an array of objects with a common property which you wish to sum up,
     * you can use the sum( ) function, specifying the array slash the property.
     * Those objects which don't have the prop, or whose said prop is not a number, won't be
     * taken into account in the summation.
     */
    public function testSumOfProperties()
    {
        $this->view->mount('myArray', array(
            array('price' => 10.5), // adds up
            array('price' => 14.6), // adds up
            array('noPriceHere' => 10), // is discarded
            array('price' => 'Some String'), // is discarded
            array('price' => 7) // adds up
        ));
        $this->assertEquals(14.6, $this->lexExpr(' /myArray/1/price '));
        $this->assertEquals(32.1, $this->lexExpr(' sum( /myArray/price ) '));
    }

    public function testGlobalConst()
    {
        define('MY_GLOBAL_TEST_CONST', 12);
        $this->assertEquals(MY_GLOBAL_TEST_CONST, $this->lexExpr(" const( 'MY_GLOBAL_TEST_CONST' ) "));
    }

    public function testGlobalConstUndefinedReturnsConstName()
    {
        $randomValue = 'CERTAINLY_UNDEFINED_FIGDICE_TEST_CONSTANT_' . mt_srand();
        $this->assertEquals($randomValue, $this->lexExpr(" const( '$randomValue' ) "));
    }

    public function testClassConstForUndefinedClassIsNull()
    {
        $this->assertNull($this->lexExpr(" const( 'MyTestUndefinedClass::someConst' ) "));
    }

    const SOME_CONST = 12;

    public function testClassConst()
    {
        $this->assertEquals(self::SOME_CONST, $this->lexExpr(" const( 'NativeFunctionFactoryTest::SOME_CONST' ) "));
    }

    public function testClassConstWithNamespaceNeedDoubleBackslash()
    {
        require_once 'DummyNamespaceFile.php';
        // Use NowDoc string, so that PHP will not compile \\ into \,
        // but will pass the double \\ through the FigDice expression lexer.
        $expression = <<<'ENDSTRING'
	  const( '\\some\\dummy\\ns\\SomeDummyClass::SOME_CONST' )
ENDSTRING;
        $this->assertEquals(13, $this->lexExpr($expression));
    }

    public function testDefaultFunc()
    {
        $this->assertEquals(12, $this->lexExpr(" default( /someDummyPath, 12 ) "));
    }

    public function testFunctionFirst()
    {
        $this->view->mount('data', array(1, 2, 3));
        $source = <<<ENDXML
<fig:x fig:walk="/data"><fig:y fig:text="if(first(), 't', 'f')"/></fig:x>
ENDXML;
        $this->view->loadString($source);
        $expected = <<<ENDHTML
tff
ENDHTML;
        $actual = $this->view->render();
        $this->assertEquals($expected, $actual);
    }

    public function testFunctionLast()
    {
        $this->view->mount('data', array(1, 2, 3));
        $source = <<<ENDXML
<fig:x fig:walk="/data"><fig:y fig:text="if(last(), 't', 'f')"/></fig:x>
ENDXML;
        $this->view->loadString($source);
        $expected = <<<ENDHTML
fft
ENDHTML;
        $actual = $this->view->render();
        $this->assertEquals($expected, $actual);
    }


    /**
     * This test is not the same as the one in LexerTest which expects also
     * this exception. In the situation below, we DID register a NativeFunctionFactory.
     */
    public function testUnfedinedFunc()
    {
        $this->expectException(FunctionNotFoundException::class);
        $this->assertEquals(false, $this->lexExpr(" someUndefinedFunc(1, 2, 3)  "));
    }

    public function testSubstrFuncWithMissingArgumentsThrowsException()
    {
        $this->expectException(FunctionCallException::class);
        $this->lexExpr(" substr('abcd') ");
    }

    public function testSubstrFuncWith3Args()
    {
        $this->assertEquals('cd', $this->lexExpr("substr('abcde', 2, 2)"));
    }

    public function testSubstrFuncWithoutLengthReturnsRight()
    {
        $this->assertEquals('cd', $this->lexExpr("substr('abcd', 2)"));
    }

    public function testPositionFunc()
    {
        $this->view->mount('data', array(10, 20, 30));
        $source = <<<ENDXML
<fig:x fig:walk="/data"><fig:y fig:text="position()"/></fig:x>
ENDXML;
        $this->view->loadString($source);
        $this->assertEquals('123', $this->view->render());
    }

    public function testEvenFunc()
    {
        $this->view->mount('data', array(10, 20, 30));
        $source = <<<ENDXML
<fig:x fig:walk="/data"><fig:y fig:text="if(even(), 'e', 'o')"/></fig:x>
ENDXML;
        $this->view->loadString($source);
        $this->assertEquals('oeo', $this->view->render());
    }

    public function testOddFunc()
    {
        $this->view->mount('data', array(10, 20, 30));
        $source = <<<ENDXML
<fig:x fig:walk="/data"><fig:y fig:text="if(odd(), 'o', 'e')"/></fig:x>
ENDXML;
        $this->view->loadString($source);
        $this->assertEquals('oeo', $this->view->render());
    }

    public function testKeyFunc()
    {
        $this->view->mount('data', array('a' => 10, 'b' => 20, 'c' => 30));
        $source = <<<ENDXML
<fig:x fig:walk="/data"><fig:y fig:text="key()"/><fig:y fig:text="."/></fig:x>
ENDXML;
        $this->view->loadString($source);
        $this->assertEquals('a10b20c30', $this->view->render());
    }

    public function testPhpFunc()
    {
        $source = <<<ENDXML
<fig:x fig:text="php('strlen', 'string of 18 chars')" />
ENDXML;
        $this->view->loadString($source);
        $this->assertEquals(18, $this->view->render());
    }

    public function testRangeFunc()
    {
        $source = <<<ENDXML
<li fig:walk="range(5)" fig:text="."></li>
ENDXML;
        $this->view->loadString($source);
        $this->assertEquals('<li>1</li><li>2</li><li>3</li><li>4</li><li>5</li>', $this->view->render());
    }

    public function testInvalidPhpFuncReturnsFalse()
    {
        $this->assertFalse($this->lexExpr("php('no_chance_that_this_function_exists_in_php', 12)"));
    }

    public function testAverage()
    {
        $this->view->mount('values', [
            ['x' => 7],
            ['x' => 21],
            ['x' => 17],
        ]);

        $this->assertEquals(15.0, $this->lexExpr('average(/values/x)'));
    }

    public function testFormatNumberWithAlphaReturnsEmpty()
    {
        $this->assertEmpty($this->lexExpr("format_number( 'abc' )"));
    }

    public function testFormatNumberWithOneArgUsesDefaults()
    {
        $this->assertEquals('1 234,10', $this->lexExpr("format_number( 1234.1 )"));
    }

    public function testFormatNumberWithExplicitDecimals()
    {
        $this->assertEquals('1 234,100', $this->lexExpr("format_number( 1234.1, 3 )"));
    }

    public function testFormatNumberWithExplicitFloatingPoint()
    {
        $this->assertEquals('1 234P100', $this->lexExpr("format_number( 1234.1, 3, 'P' )"));
    }

    public function testFormatNumberWithExplicitThousandSep()
    {
        $this->assertEquals('1K234.100', $this->lexExpr("format_number( 1234.1, 3, '.', 'K' )"));
    }
}
