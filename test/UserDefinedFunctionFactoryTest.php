<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
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

use figdice\FigFunction;
use figdice\FunctionFactory;
use figdice\View;
use figdice\classes\File;
use figdice\classes\lexer\Lexer;
use figdice\classes\ViewElementTag;
use figdice\exceptions\LexerUnexpectedCharException;


/**
 * Unit Test Class for Function factory and custom Fig functions
 */
class UserDefinedFunctionFactoryTest extends PHPUnit_Framework_TestCase {

	protected $viewElement;

	protected function setUp() {
		$this->viewElement = null;
	}
	protected function tearDown() {
		$this->viewElement = null;
	}

	private function lexExpr($expression) {
		$lexer = new Lexer($expression);

		if (null == $this->viewElement) {
			$viewElement = $this->prepareViewElement();
		}
		else {
			$viewElement = $this->viewElement;
		}

		// Make sure that the passed expression is successfully parsed,
		// before asserting stuff on its evaluation.
		$parseResult = $lexer->parse($viewElement);
		$this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

		return $lexer->evaluate($viewElement);
	}

	/**
	 * @return ViewElement
	 */
	private function prepareViewElement() {
		// A Lexer object needs to live inside a View,
		// and be bound to a ViewElementTag instance.
		// They both need to be bound to a File object,
		// which must respond to the getCurrentFile method.


		// In this test, we need a real View object, because
		// it embeds a real NativeFunctionFactory instance.
		$view = new View();

		$viewFile = $this->getMock('\\figdice\\classes\\File', null, array('PHPUnit'));
		$viewElement = $this->getMock('\\figdice\\classes\\ViewElementTag', array('getCurrentFile'), array(& $view, 'testtag', 12));
		$viewElement->expects($this->any())
			->method('getCurrentFile')
			->will($this->returnValue($viewFile));

		$this->viewElement = $viewElement;
		return $viewElement;
	}

	/**
	 * @expectedException \figdice\exceptions\FunctionNotFoundException
	 */
	public function testCustomFunctionBeforeRegThrowsException()
	{
		$this->lexExpr( "customFunc(12)" );
		$this->assertFalse(true);
	}

	public function testRegisteredCustomFunctionExecutes()
	{
		$viewElement = $this->prepareViewElement();
		$view = $viewElement->getView();

		//Create an instance of our custom factory
		$functionFactory = new CustomFunctionFactory();

		//Register our nice factory
		$view->registerFunctionFactory($functionFactory);

		//and evaluate an expression which invokes our function.
		//We use the custom func twice, in order to check coverage for the caching mechanism
		//of user-defined function instances.
		$result = $this->lexExpr( "customFunc(12) + customFunc(13)" );
		$this->assertEquals(12*2 + 13*2, $result);
	}

}

/**
 * This simple function factory handles one function: customFunc
 * and does not bother with caching.
 */
class CustomFunctionFactory extends FunctionFactory {
	public function create($funcName) {
		if ($funcName == 'customFunc') {
			return new MyCustomFigFunc();
		}
		return null;
	}
}

/**
 * This simple function accepts one argument, and
 * returns the argument multiplied by 2.
 */
class MyCustomFigFunc implements FigFunction {
	public function evaluate(ViewElementTag $viewElement, $arity, $arguments) {
		if($arity < 1) {
			return null;
		}
		$firstArgument = $arguments[0];
		return 2 * $firstArgument;
	}
}
