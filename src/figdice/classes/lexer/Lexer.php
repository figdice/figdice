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

namespace figdice\classes\lexer;

use Exception;
use figdice\classes\Context;
use figdice\classes\ViewElementTag;
use figdice\exceptions\LexerUnexpectedCharException;
use figdice\exceptions\LexerSyntaxErrorException;
use figdice\exceptions\LexerUnbalancedParenthesesException;

class Lexer {

    /** @var Context */
    private $context;

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
     * @param Context $context
     *
     * @return bool
     * @throws LexerSyntaxErrorException
     * @throws LexerUnbalancedParenthesesException
     * @throws LexerUnexpectedCharException
     */
	public function parse(Context $context) {
	    $this->context = $context;
		$this->viewElement = $context->tag;

		//Interpret an empty expression as boolean false.
		if(trim($this->expression) == '') {
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
					// Check that we have enough operands on the stack.
					if (sizeof($this->stackRP) < $nbOperands) {
						throw new LexerSyntaxErrorException('Missing operand', $this->getViewFile(), $this->getViewLine());
					}
					$operator->setOperands(array_splice($this->stackRP, sizeof($this->stackRP) - $nbOperands, $nbOperands));
				}
				$this->stackRP[] = $operator;
				if($operator instanceof TokenFunction)
					array_pop($this->stackFunctions);
			}
		}
		catch (Exception $exception) {
			$errorMsg = "Unexpected character: $char at position: {$this->parsingPosition} in expression: {$this->expression}.";
			throw new LexerUnexpectedCharException($errorMsg, $this->getViewFile(), $this->getViewLine());
		}

		if( (count($this->stackOperators) > 0) ||
				(count($this->stackFunctions) > 0) ||
				(count($this->stackRP) != 1)
			)
		{
			$message = $this->getViewFile() . '(' . $this->getViewLine() . '): Syntax error in expression: ' . $this->expression;
			throw new LexerSyntaxErrorException($message,
					$this->getViewFile(),
					$this->getViewLine());
		}

		// Check for unbalanced parentheses:
		if( ($this->stackRP[0] instanceof TokenFunction) &&
		    (! $this->stackRP[0]->isClosed())
		)
		{
		  $message = $this->getViewFile() . '(' . $this->getViewLine() . '): Unbalanced parentheses in expression: ' . $this->expression;
		  throw new LexerUnbalancedParenthesesException($message,
		    $this->getViewFile(),
		    $this->getViewLine());
		}

		return true;
	}

	/**
	 * @param string $char
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
	 * @param string $char
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
	 * @param Token $tokenOperand
	 */
	public function pushOperand($tokenOperand) {
		$this->stackRP[] = $tokenOperand;
	}

    /**
     * @param TokenOperator $tokenOperator
     *
     * @throws LexerSyntaxErrorException
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
            // Check that we have enough operands on the stack.
            if (sizeof($this->stackRP) < $nbOperands) {
              $message = 'Missing operand in expression: ' . $this->expression;
              throw new LexerSyntaxErrorException($message, $this->getViewFile(), $this->getViewLine());
            }
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
				if (count($this->stackRP) < $nbOperands) {
					throw new LexerSyntaxErrorException('Not enough arguments in: ' . $this->getExpression(), $this->getViewFile(), $this->getViewLine());
				}
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

	/**
	 * Create a wrappable Expression out of what's inside the [],
	 * and append it as a path element to current path.
	 * @throws Exception
	 */
	public function closeSquareBracket() {
		while(0 < count($this->stackOperators)) {
			$operator = array_pop($this->stackOperators);
			if($operator instanceof TokenLBracket) {
				break;
			}

			// The square bracket expression contains operators. ex: [i + 1]
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
	 * @param PathElement|string $path
	 */
	public function pushPath($path) {
		$this->pushOperand(new TokenPath($path));
		$this->setStatePath();
	}

	/**
	 * Prerequisite to this function: the last element in stack
	 * MUST be a TokenPath. It is normally always the case, given the usage of
	 * this function. If you think you need to call Lexer::pushPathElement by
	 * yourself from somewhere, make sure your last stack elt is a TokenPath.
	 * @param $buffer
	 */
	public function pushPathElement($buffer) {
		/** @var TokenPath $tokenPath */
		$tokenPath = $this->stackRP[count($this->stackRP) - 1];
		$tokenPath->appendElement($buffer);
	}

	public function incrementLastFunctionArity() {
		 
		if ( ($funcStackCount = count($this->stackFunctions)) == 0) {
			// There isn't a function to which we're incrementing arity!
			$errorMsg = $this->getViewFile() . '(' . $this->getViewLine() . '): Unbalanced parentheses in expression: "' . $this->expression . '"';
			throw new LexerUnbalancedParenthesesException($errorMsg,
				$this->getViewFile(),
				$this->getViewLine());
		}

    // Grab the topmost function on the stack. That's the one we're dealing with.
		$tokenFunction = $this->stackFunctions[$funcStackCount - 1];
		$this->setStateEmpty();
		
		//Pop operators up to the function:
    //the function of interest (the one to which we're pushing a comma) is the topmost TokenFunction
    //in the stack of operators. Every other operator must be popped and his operands popped+attached.
		//An intermediate function ( example: func2 in "func1(func2(x),y)"   ) is no longer a TokenFunction in
		//the stack of operators at this stage. It already became an operand at the ")" before the ",".
		while( (! ($this->stackOperators[count($this->stackOperators) - 1]) instanceof TokenFunction) ) {
			$operator = array_pop($this->stackOperators);
			$nbOperands = $operator->getNumOperands();
			if($nbOperands) {
				$operator->setOperands(array_splice($this->stackRP, sizeof($this->stackRP) - $nbOperands, $nbOperands));
			}
			$this->stackRP[] = $operator;
		}
		$tokenFunction->incrementArity();
	}


	public function forwardInput($char) {
		$this->currentState->input($this, $char);
	}

	/**
	 * @param Context $context
	 * @return mixed
	 */
	public function evaluate(Context $context) {
	    $this->context = $context;
		$this->viewElement = $context->tag;
		return $this->stackRP[0]->evaluate($context);
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
	 * @return string
	 */
	public function getViewFile() {
		return $this->context->getFilename();
	}
	public function getViewLine() {
		return $this->viewElement->getLineNumber();
	}
}
