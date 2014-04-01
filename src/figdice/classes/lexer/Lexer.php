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

namespace figdice\classes\lexer;

use \figdice\classes\ViewElementTag;
use \figdice\exceptions\LexerUnexpectedCharException;
use \figdice\exceptions\LexerSyntaxErrorException;
use \figdice\exceptions\LexerUnbalancedParentheses;
use \figdice\LoggerFactory;
use Psr\Log\LoggerInterface;

class Lexer {
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var ViewElementTag
	 */
	private $viewElement;

	/**
	 * @var string
	 */
	private $expression;

	/**
	 * @var DFAState
	 */
	private $currentState;

	/**
	 * @var DFAStateEmpty
	 */
	private static $stateEmpty = null;
	/**
	 * @var DFAStateSymbol
	 */
	private static $stateSymbol = null;
	/**
	 * @var DFAStateInteger
	 */
	private static $stateInteger = null;
	/**
	 * @var DFAStateDecimal
	 */
	private static $stateDecimal = null;
	/**
	 * @var DFAStateFunction
	 */
	private static $stateFunction = null;
	/**
	 * @var DFAStateComparison
	 */
	private static $stateComparison = null;
	/**
	 * @var DFAStateString
	 */
	private static $stateString = null;
	/**
	 * @var DFAStateClosedExpression
	 */
	private static $stateClosedExpression = null;
	/**
	 * @var DFAStatePath
	 */
	private static $statePath = null;
	/**
	 * @var DFAStateDot
	 */
	private static $stateDot = null;
	/**
	 * @var DFAStateDotdot
	 */
	private static $stateDotdot = null;

	/**
	 * @var array
	 */
	private $stackOperators;
	/**
	 * @var array
	 */
	private $stackFunctions;

	/**
	 * @var array
	 */
	private $stackRP;

	/**
	 * The position of the character being parsed, in expression,
	 * during parse-time.
	 * @var integer
	 */
	private $parsingPosition;

	public function __construct($expression) {
		$this->expression = $expression;

		if(! self::$stateEmpty) {
			self::$stateEmpty = new DFAStateEmpty();
			self::$stateSymbol = new DFAStateSymbol();
			self::$stateInteger = new DFAStateInteger();
			self::$stateDecimal = new DFAStateDecimal();
			self::$stateFunction = new DFAStateFunction();
			self::$stateComparison = new DFAStateComparison();
			self::$stateString = new DFAStateString();
			self::$stateClosedExpression = new DFAStateClosedExpression();
			self::$statePath = new DFAStatePath();
			self::$stateDot = new DFAStateDot();
			self::$stateDotdot = new DFAStateDotdot();
		}

		$this->currentState = self::$stateEmpty;			
	}

	/**
	 * Returns the tree representing the parsed expression.
	 * @return array
	 */
	public function getTree() {
		return $this->stackRP[0];
	}

	/**
	 * @param ViewElement $viewElement
	 * @return boolean
	 */
	public function parse($viewElement) {
		$this->viewElement = $viewElement;

		//Interpret an empty expression as boolean false.
		if(trim($this->expression == '')) {
		  $this->pushOperand(new TokenLiteral(false));
		  return true;
		}

		$char = '';

		try {
			$expressionSize = strlen($this->expression);
			for($this->parsingPosition = 0; $this->parsingPosition < $expressionSize; ++ $this->parsingPosition) {
				$char = $this->expression[$this->parsingPosition];
				$this->currentState->input($this, $char);
			}

			$this->currentState->endOfInput($this);

			while(sizeof($this->stackOperators)) {
				$operator = array_pop($this->stackOperators);
				$nbOperands = $operator->getNumOperands();
				if($nbOperands) {
					$operator->setOperands(array_splice($this->stackRP, sizeof($this->stackRP) - $nbOperands, $nbOperands));
				}
				$this->stackRP[] = $operator;
				if($operator instanceof TokenFunction)
					array_pop($this->stackFunctions);
			}
		}
		catch (Exception $exception) {
			$errorMsg = "Unexpected character: $char at position: {$this->parsingPosition} in expression: {$this->expression}.";
			$message = $this->getViewFile()->getFilename() . '(' . $this->getViewLine() . '): ' . $errorMsg ;
			$this->getLogger()->error($message);
			throw new LexerUnexpectedCharException($errorMsg, $this->getViewFile()->getFilename(), $this->getViewLine());
		}

		if( (count($this->stackOperators) > 0) ||
				(count($this->stackFunctions) > 0) ||
				(count($this->stackRP) != 1)
			)
		{
			$message = $this->getViewFile()->getFilename() . '(' . $this->getViewLine() . '): Syntax error in expression: ' . $this->expression;
			$this->getLogger()->error($message);
			throw new LexerSyntaxErrorException($message,
					$this->getViewFile()->getFilename(),
					$this->getViewLine());
		}

		return true;
	}

	/**
	 * @param char $char
	 */
	public function setStateSymbol($char)
	{
		$this->currentState = self::$stateSymbol;
		$this->currentState->setBuffer($char);
	}
	public function setStateDot() {
		$this->currentState = self::$stateDot;
	}
	/**
	 * @param char $char
	 */
	public function setStateInteger($char)
	{
		$this->currentState = self::$stateInteger;
		$this->currentState->setBuffer($char);
	}

	public function setStateDecimal($buffer)
	{
		$this->currentState = self::$stateDecimal;
		$this->currentState->setBuffer($buffer);
	}
	public function setStateEmpty() {
		$this->currentState = self::$stateEmpty;
	}

	public function setStateFunction($name) {
		$this->currentState = self::$stateFunction;
		$this->currentState->setBuffer($name);
	}

	public function setStatePath($separated = true, $closedSubpath = false) {
		$this->currentState = self::$statePath;
		$this->currentState->separate($separated);
		$this->currentState->setBuffer('');
		$this->currentState->setClosedSubpath($closedSubpath);
	}
	public function setStateDotdot() {
		$this->currentState = self::$stateDotdot;
	}
	/**
	 * @param string $string
	 */
	public function setStateComparison($string) {
		$this->currentState = self::$stateComparison;
		$this->currentState->setBuffer($string);
	}

	public function setStateString() {
		$this->currentState = self::$stateString;
		$this->currentState->setBuffer('');
	}
	public function setStateClosedExpression() {
		$this->currentState = self::$stateClosedExpression;
	}

	/**
	 * @param TokenOperand $tokenOperand
	 */
	public function pushOperand($tokenOperand) {
		$this->stackRP[] = $tokenOperand;
	}
	/**
	 * @param TokenOperator $tokenOperator
	 */
	public function pushOperator($tokenOperator) {
		//If priority of the new operator
		//is lower than or equal to that of the previous, if any,
		//then pop the stack up to the nearest operator
		//of lower priority strictly, or up to left parenthesis.

		if( (! $tokenOperator instanceof TokenLParen) && 
		 		(! $tokenOperator instanceof TokenFunction) &&
		 		(! $tokenOperator instanceof TokenLBracket)
			) {
			while(sizeof($this->stackOperators) > 0) {
				$operator = $this->stackOperators[sizeof($this->stackOperators) - 1];	
				if($operator instanceof TokenLParen) {
					break;
				}

				if( ($operator instanceof TokenFunction) && (! $operator->isClosed()) ) {
					break;
				}

				if( $tokenOperator->getPriority() <= $operator->getPriority() ) {
					array_pop($this->stackOperators);

					$nbOperands = $operator->getNumOperands();
					if($nbOperands) {
						$operator->setOperands(array_splice($this->stackRP, sizeof($this->stackRP) - $nbOperands, $nbOperands));
					}
					$this->stackRP[] = $operator;

					if($operator instanceof TokenFunction) {
						array_pop($this->stackFunctions);
						break;
					}
				}
				else {
					break;
				}
			}
		}

		$this->stackOperators[] = $tokenOperator;
		if($tokenOperator instanceof TokenFunction) {
			$this->stackFunctions[] = &$tokenOperator;
		}
		$this->setStateEmpty();
	}

	public function closeParenthesis() {
		while(0 < sizeof($this->stackOperators)) {
			$operator = array_pop($this->stackOperators);
			if($operator instanceof TokenLParen) {
				break;
			}

			$nbOperands = $operator->getNumOperands();
			if($nbOperands) {
				$operator->setOperands(array_splice($this->stackRP, sizeof($this->stackRP) - $nbOperands, $nbOperands));
			}
			$this->stackRP[] = $operator;

			if($operator instanceof TokenFunction) {
				$operator->close();
				array_pop($this->stackFunctions);
				break;
			}
		}

		$this->setStateClosedExpression();
	}

	public function closeSquareBracket() {
		while(0 < count($this->stackOperators)) {
			$operator = array_pop($this->stackOperators);
			if($operator instanceof TokenLBracket) {
				break;
			}

			$nbOperands = $operator->getNumOperands();
			if($nbOperands) {
				$operator->setOperands(array_splice($this->stackRP, sizeof($this->stackRP) - $nbOperands, $nbOperands));
			}
			$this->stackRP[] = $operator;

		}

		$operand = array_pop($this->stackRP);
		$path = new Expression($operand);
		$this->pushPathElement($path);
		$this->setStatePath(false, true);
	}

	/**
	 * @param PathElement (or string) $path
	 */
	public function pushPath($path) {
		$this->pushOperand(new TokenPath($path));
		$this->setStatePath();
	}
	public function pushPathElement($buffer) {
		if(! $this->stackRP[count($this->stackRP) - 1] instanceof TokenPath) {
			$message = $this->getViewFile() . '(' . $this->getViewLine() . "): Syntax error in expression: {$this->expression}.";
			$this->getLogger()->error($message);
			throw new Exception($message);
		}
		$this->stackRP[count($this->stackRP) - 1]->appendElement($buffer);
	}

	public function incrementLastFunctionArity() {
		 
		if ( ($funcStackCount = count($this->stackFunctions)) == 0) {
			$errorMsg = $this->getViewFile()->getFilename() . '(' . $this->getViewLine() . '): Unbalanced parentheses in expression: "' . $this->expression . '"';
			$this->getLogger()->error($errorMsg);
			throw new LexerUnbalancedParentheses($errorMsg, 
				$this->getViewFile()->getFilename(), 
				$this->getViewLine());
		}
		$tokenFunction = $this->stackFunctions[$funcStackCount - 1];
		$this->setStateEmpty();
		
		//Pop operators up to the function:
		//an intermediate operator to pop could be a function, provided that it is closed
		//(although I think it can never happen, for when a function is closed, it becomes an operand
		//and is no longer in the stack of operators... 
		while( (! ($operator = $this->stackOperators[count($this->stackOperators) - 1]) instanceof TokenFunction) || $operator->isClosed()) { 
			$operator = array_pop($this->stackOperators);
			$nbOperands = $operator->getNumOperands();
			if($nbOperands) {
				$operator->setOperands(array_splice($this->stackRP, sizeof($this->stackRP) - $nbOperands, $nbOperands));
			}
			$this->stackRP[] = $operator;
		}
		$tokenFunction->incrementArity();
	}

	/**
	 * @return LoggerInterface
	 */
	private function getLogger() {
		if(null == $this->logger) {
			$this->logger = LoggerFactory::getLogger(__CLASS__);
		}
		return $this->logger;
	}

	public function forwardInput($char) {
		$this->currentState->input($this, $char);
	}

	/**
	 * @param ViewElement $viewElement
	 * @return mixed
	 */
	public function evaluate(ViewElementTag $viewElement) {
		$this->viewElement = $viewElement;
		return $this->stackRP[0]->evaluate($viewElement);
	}

	/**
	 * @return string
	 */
	public function getExpression() {
		return $this->expression;
	}

	/**
	 * Returns the file containing the current
	 * expression to evaluate.
	 *
	 * @return File
	 */
	public function getViewFile() {
		return $this->viewElement->getCurrentFile();
	}
	public function getViewLine() {
		return $this->viewElement->getLineNumber();
	}
}
