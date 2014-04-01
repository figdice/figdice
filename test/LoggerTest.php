<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
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
use figdice\LoggerFactory;
use figdice\LoggerFactoryDelegate;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

/**
 * Unit Test Class for PRS-3 Logging integration
 */
class LoggerTest extends PHPUnit_Framework_TestCase {

	private function lexExpr($expression) {
		$lexer = new Lexer($expression);

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

		// Make sure that the passed expression is successfully parsed,
		// before asserting stuff on its evaluation.
		$parseResult = $lexer->parse($viewElement);
		$this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

		return $lexer->evaluate($viewElement);
	}




	/**
	 * In this test, we will evaluate an undefined const,
	 * because we know that the Function_const class is designed to 
	 * log a warning.
	 * One can wrap any implementation of the PSR-3 Logging they wish,
	 * by supplying to FigDice a LoggerFactoryDelegate.
	 * You supply a delegate by giving the FigDice's LoggerFactory static
	 * utility class, an instance of figdice\LoggerFactoryDelegate.
	 * This instance accepts the name of a class, and provides the corresponding
	 * PSR-3 LoggerInterface of your choice.
	 */
	public function testUnfedinedConstWillWarn() {
		
		$capturableLoggerInterface = new YouHaveBeenWarned();
		
		LoggerFactory::setDelegate(new TestLoggerFactoryDelegate($capturableLoggerInterface));
		$result = $this->lexExpr( " const('myUndefinedConst')  ");
		$this->assertEquals(false,  $result);
		$this->assertContains('Undefined constant: myUndefinedConst', 
				$capturableLoggerInterface->getCapturedWarnings());
	}
}

class YouHaveBeenWarned extends Psr\Log\AbstractLogger
{
	private $capturedWarnings = array();
	public function warning($message, array $context = array()) {
		$this->capturedWarnings []= $message;
	}
	
	public function getCapturedWarnings() {
		return $this->capturedWarnings;
	}
	
	public function log($level, $message, array $context = array()) {
	}
}
class TestLoggerFactoryDelegate implements LoggerFactoryDelegate
{
	private $logger;
	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}
	public function getLogger($class) {
		//Let's provide a class-independent, unique instance of
		//logger which captures the warnings.
		return $this->logger; 
	}
}
