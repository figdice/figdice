<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 */
declare(strict_types=1);

use figdice\exceptions\FunctionNotFoundException;
use PHPUnit\Framework\TestCase;

use figdice\classes\Context;
use figdice\classes\ViewElementTag;
use figdice\FigFunction;
use figdice\FunctionFactory;
use figdice\View;
use figdice\classes\lexer\Lexer;


/**
 * Unit Test Class for Function factory and custom Fig functions
 */
class UserDefinedFunctionFactoryTest extends TestCase {

    /** @var \figdice\classes\ViewElement */
	protected $viewElement;
	/** @var View */
	protected $view;

	protected function setUp(): void {
		$this->viewElement = null;
	}
	protected function tearDown(): void {
		$this->viewElement = null;
	}

	private function lexExpr($expression) {
		$lexer = new Lexer($expression);

		if (null == $this->viewElement) {
			$this->prepareViewElement();
		}

		$context = new Context($this->view);
		$context->tag = $this->viewElement;

		// Make sure that the passed expression is successfully parsed,
		// before asserting stuff on its evaluation.
		$parseResult = $lexer->parse($context);
		$this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

		return $lexer->evaluate(new Context($this->view));
	}

	private function prepareViewElement() {
		// A Lexer object needs to live inside a View,
		// and be bound to a ViewElementTag instance.
		// They both need to be bound to a File object,
		// which must respond to the getCurrentFile method.


		// In this test, we need a real View object, because
		// it embeds a real NativeFunctionFactory instance.
		$view = new View();
		$this->view = $view;

		$viewElement = $this->createMock(ViewElementTag::class);

		$this->viewElement = $viewElement;
    }

	public function testCustomFunctionBeforeRegThrowsException()
	{
	    $this->expectException(FunctionNotFoundException::class);
		$this->lexExpr( "customFunc(12)" );
	}

	public function testRegisteredCustomFunctionExecutes()
	{
        $this->prepareViewElement();
        $view = $this->view;

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
	public function evaluate(Context $context, $arity, $arguments) {
		if($arity < 1) {
			return null;
		}
		$firstArgument = $arguments[0];
		return 2 * $firstArgument;
	}
}
