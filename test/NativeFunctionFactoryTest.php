<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.2
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
use figdice\exceptions\LexerUnexpectedCharException;
use figdice\View;
use figdice\classes\File;
use figdice\classes\ViewElementTag;

/**
 * Unit Test Class for basic Lexer expressions
 */
class NativeFunctionFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var View
	 */
	private $view;
	
	protected function setUp() {
		$this->view = new View();
	}
	private function lexExpr($expression) {
		$lexer = new Lexer($expression);

		// A Lexer object needs to live inside a View,
		// and be bound to a ViewElementTag instance.
		// They both need to be bound to a File object,
		// which must respond to the getCurrentFile method.


		// In this test, we need a real View object, because
		// it embeds a real NativeFunctionFactory instance.
		$view = $this->view;

		$viewFile = $this->getMock('\\figdice\\classes\\File', null, array('PHPUnit'));
		$viewElement = $this->getMock('\\figdice\\classes\\ViewElementTag', array('getCurrentFile'), array(& $view, 'testtag', 12));
		$viewElement->expects($this->any())
			->method('getCurrentFile')
			->will($this->returnValue($viewFile));

		// Make sure that the passed expression is successfully parsed,
		// before asserting stuff on its evaluation.
		$parseResult = $lexer->parse($viewElement);
		$this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

		return $lexer->evaluate($viewElement);
	}



	public function testFunctionGETdoesNotProvideAnythingInCLI()
	{
		$this->assertEquals(0, $this->lexExpr( "GET('x')" ));
	}
	public function testFunctionMockGETinCLI()
	{
		$_GET['foo'] = 'bar';
		$this->assertEquals('bar', $this->lexExpr( "GET('foo')" ));
	}
	public function testIfFuncForYesOperand() {
		$this->assertEquals('yes', $this->lexExpr( " if(true, 'yes', 'no')  " ));
	}
	public function testIfFuncForNoOperand() {
		$this->assertEquals('no', $this->lexExpr( " if(false, 'yes', 'no')  " ));
	}
	public function testCountOfEmptyIsZero() {
		$this->assertEquals(0, $this->lexExpr(' count(/dummy/value) '));
	}
	public function testCountOfLiteralIsZero() {
		$this->assertEquals(0, $this->lexExpr(' count( 12 ) '));
	} 
	public function testCountOfArrayIsCount() {
		$this->view->mount('myArray', array(1,2,3));
		$this->assertEquals(3, $this->lexExpr(' count( /myArray ) '));
	} 
	public function testSumOfArrayIsOk() {
		$this->view->mount('myArray', array(1,2,3));
		$this->assertEquals(6, $this->lexExpr(' sum( /myArray ) '));
	}
	public function testGlobalConst() {
		define('MY_GLOBAL_TEST_CONST', 12);
		$this->assertEquals(MY_GLOBAL_TEST_CONST, $this->lexExpr(" const( 'MY_GLOBAL_TEST_CONST' ) ") );
	}
	
	/**
	 * This test is not the same as the one in LexerTest which expects also
	 * this exception. In the situation below, we DID register a NativeFunctionFactory.
	 * @expectedException \figdice\exceptions\FunctionNotFoundException
	 */
	public function testUnfedinedFunc() {
		$this->assertEquals(false, $this->lexExpr( " someUndefinedFunc(1, 2, 3)  " ));
	}
}
