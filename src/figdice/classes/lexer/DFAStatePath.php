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

class DFAStatePath extends DFAState {
	/**
	 * This flag indicates whether the input is currently
	 * in the middle of a path element, or at the beginning of it.
	 * Separated = true means: we are right after a Slash.
	 *
	 * @var boolean
	 */
	private $separated;
	/**
	 * This flag indicates whether we are in a state where
	 * a subpath has just been closed (i.e.: right after a ']').
	 * In such state, the valid inputs are '/', blank, ')', ']' or end.
	 *
	 * @var bool
	 */
	private $closedSubpath;

	public function __construct() {
		parent::__construct();
		$this->separated = true;
	}

	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	public function input(Lexer $lexer, $char) {
		//Valid inputs while in state Path are:
		//- any alphanum if not closed,
		//- opening [ if not closed and separated,
		//- slash if not closed and not separated,
		//- opening ( if not separated,
		//- closing ) always
		//- , (comma) always
        //- . (dot) if 'dotting'. The dotting state is when all the previous buffer in the path
        //                        is only a succession of dots (or dotdots) and slashes.

		if($char == ',') {
			if( (! $this->closedSubpath) && (! $this->closed) ) {
				$lexer->pushPathElement($this->buffer);
			}
			$lexer->incrementLastFunctionArity();
		}
		else if($char == ')') {
			if( (! $this->closedSubpath) && (! $this->closed) ) {
				$lexer->pushPathElement($this->buffer);
			}
			$lexer->closeParenthesis();
		}
		else if($char == '[') {
			if( (! $this->closed) && ($this->separated) && (! $this->closedSubpath)) {
				$lexer->pushOperator(new TokenLBracket());
			}
			else {
				$this->throwError($lexer, $char);
			}
		}
		//Closing a subpath:
		else if($char == ']') {
			$lexer->pushPathElement($this->buffer);
			$lexer->closeSquareBracket();
		}
		else if($char == '/') {
			if(! $this->separated) {
				$this->separated = true;
				if( (! $this->closed) && (! $this->closedSubpath) )
					$lexer->pushPathElement($this->buffer);
				$this->closed = false;
				$this->buffer = '';
				$this->closedSubpath = false;
			}
			else {
				$this->throwError($lexer, $char);
			}
		}
		else if(self::isAlphaNum($char)) {
			if(! $this->closedSubpath) {
				$this->buffer .= $char;
				$this->separated = false;
			}
			else {
				$this->throwError($lexer, $char);
			}
		}
		else if(self::isBlank($char)) {
			$this->closed = true;
			if(! $this->closedSubpath) {
				$lexer->pushPathElement($this->buffer);
			}
			$lexer->setStateClosedExpression();
		}
		else if( ($char == '(') && (! $this->separated) ){
			$lexer->setStateFunction($this->buffer);
		}
		else if(($char == '=') || ($char == '!') ) {
			$this->closed = true;
			if(! $this->closedSubpath) {
				$lexer->pushPathElement($this->buffer);
			}
			$lexer->setStateComparison($char);
		}
		else if( ($char == '+') || ($char == '-') ) {
			$this->closed = true;
			if(! $this->closedSubpath) {
				$lexer->pushPathElement($this->buffer);
			}
			$lexer->pushOperator(new TokenPlusMinus($char));
		}

		else {
			$this->throwError($lexer, $char);
		}
	}
		/**
	 * @param Lexer $lexer
	 */
	public function endOfInput($lexer) {
		if( (! $this->closed) && (! $this->closedSubpath) ) {
			$lexer->pushPathElement($this->buffer);
		}
	}
	public function separate($separate = true) {
		$this->separated = $separate;
	}
	public function setClosedSubpath($closedSubpath = true) {
		$this->closedSubpath = $closedSubpath;
	}

}
