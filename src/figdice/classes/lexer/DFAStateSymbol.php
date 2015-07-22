<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.1
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

class DFAStateSymbol extends DFAState
{
	static private $keywords = array(
		'and', 'or', 'div', 'mod', 'true', 'false', 'gt', 'gte', 'lt', 'lte', 'not'
	);
	
	/**
	 * @var boolean
	 */
	private $path;

	public function __construct() {
		parent::__construct();
		$this->path = false;
	}

	public function setBuffer($string) {
		parent::setBuffer($string);
		$this->path = false;
	}

	/**
	 * @param Lexer $lexer
	 * @param char $char
	 */
	public function input(Lexer $lexer, $char) {
		if(self::isAlphaNum($char)) {
			if(($this->closed) || 
				 ( (strlen($this->buffer) > 0) && (substr($this->buffer, -1) == '.') )
			) {
				if(! $this->closed)
					$lexer->pushOperand(new TokenSymbol($this->buffer));
				$this->closed = false;
				$this->buffer = $char;
				$this->path = false;
			}
			else {
				$this->buffer .= $char;
			}
		}
		else if($char == '(')
		{
			if($this->path) {
				$this->throwError($lexer, $char);
			}
			else if(in_array($this->buffer, self::$keywords))
			{
				$this->pushKeyword($lexer);
				$lexer->pushOperator(new TokenLParen());
			}
			else
			{
				$lexer->setStateFunction($this->buffer);
			}
		}
		else if( ($char == '+') || ($char == '-')) {
			if(! $this->closed) {
				$lexer->pushOperand(new TokenSymbol($this->buffer));
			}
			$lexer->pushOperator(new TokenPlusMinus($char));
		}
		else if($char == '*') {
			$lexer->pushOperand(new TokenSymbol($this->buffer));
			$lexer->pushOperator(new TokenMul());
		}
		else if($char == '!') {
			$lexer->setStateComparison($char);
		}
		else if(self::isBlank($char)) {
			if(! $this->closed) {
				$this->closed = true;
				if(! $this->path) {
					if(in_array($this->buffer, self::$keywords)) {
						$this->pushKeyword($lexer);
					}
					else {
						$lexer->pushOperand(new TokenSymbol($this->buffer));
					}
				}
				else {
					$lexer->pushOperand(new TokenSymbol($this->buffer));
				}
			}
		}
		else if($char == ')') {
			$lexer->pushOperand(new TokenSymbol($this->buffer));
			$lexer->closeParenthesis();
		}
		else if($char == '/') {
			$lexer->pushPath($this->buffer);
		}
		else if($char == ',') {
			if(in_array($this->buffer, self::$keywords)) {
				$this->pushKeyword($lexer);
			}
			else {
				$lexer->pushOperand(new TokenSymbol($this->buffer));
			}

			$lexer->incrementLastFunctionArity();
		}
		else if($char == '.') {
			if(($this->closed) || 
				 ( (strlen($this->buffer) > 0) && (substr($this->buffer, -1) != '/') )
			) {
				$this->throwError($lexer, $char);
			}
			else {
				$this->buffer .= $char;
			}
		}
		else if($char == '=') {
			if(! $this->closed) {
				$lexer->pushOperand(new TokenSymbol($this->buffer));
			}
			$lexer->setStateClosedExpression();
			$lexer->forwardInput($char);
		}
		else if($char == '[') {
			$this->throwError($lexer, $char);
		}
		//Closing a sub-path expression:
		else if($char == ']') {
			$lexer->pushOperand(new TokenSymbol($this->buffer));
			$lexer->closeSquareBracket();
		}
		else {
			$this->throwError($lexer, $char);
		}
			
	}

	public function endOfInput($lexer)
	{
		if( $this->path || ! in_array($this->buffer, self::$keywords) )
			$lexer->pushOperand(new TokenSymbol($this->buffer));
		else
			$this->pushKeyword($lexer);
	}

	/**
	 * @param Lexer $lexer
	 */
	private function pushKeyword($lexer)
	{
		switch($this->buffer)
		{
			case 'or':
				$lexer->pushOperator(new TokenOr());
				break;
			case 'and':
				$lexer->pushOperator(new TokenAnd());
				break;
			case 'div':
				$lexer->pushOperator(new TokenDiv());
				break;
			case 'mod':
				$lexer->pushOperator(new TokenMod());
				break;
			case 'true':
				$lexer->pushOperand(new TokenLiteral(true));
				$lexer->setStateClosedExpression();
				break;
			case 'false':
				$lexer->pushOperand(new TokenLiteral(false));
				$lexer->setStateClosedExpression();
				break;
			case 'gt':
			case 'gte':
			case 'lt':
			case 'lte':
				$lexer->pushOperator(new TokenComparisonBinop($this->buffer));
				break;
			case 'not':
				$lexer->pushOperator(new TokenNot());
				break;
		}
	}
}
