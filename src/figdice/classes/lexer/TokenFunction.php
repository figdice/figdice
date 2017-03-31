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

namespace figdice\classes\lexer;

use figdice\classes\Context;
use figdice\FigFunction;
use figdice\FunctionFactory;
use figdice\exceptions\FunctionNotFoundException;

class TokenFunction extends TokenOperator {
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var integer
	 */
	private $arity;

	/**
	 * @var FigFunction
	 */
	private $function;
	/**
	 * Becomes true when the closing parenthesis is found.
	 * Indicates that the function accepts no more arguments.
	 * @var boolean
	 */
	private $closed;
	/**
	 * @param string $name
	 * @param integer $arity
	 */
	public function __construct($name, $arity) {
		parent::__construct(self::PRIORITY_FUNCTION);
		$this->name = $name;
		$this->arity = $arity;
		$this->function = null;
		$this->closed = false;
	}

	public function incrementArity() {
		++ $this->arity;
	}

	/**
	 * @return integer
	 */
	public function getNumOperands() {
		return $this->arity;
	}

    /**
     * @param Context $context
     * @return mixed
     * @throws FunctionNotFoundException
     */
	public function evaluate(Context $context) {
		if($this->function === null) {
			//Instantiate the Function handler:
      /** @var FunctionFactory[] $factories */
			$factories = $context->view->getFunctionFactories();
			if ( (null != $factories) && (is_array($factories) ) ) {

        foreach ($factories as $factory) {
          if(null !== ($this->function = $factory->lookup($this->name)))
            break;
        }
			}

			if($this->function == null) {
				throw new FunctionNotFoundException($this->name);
			}
		}

		$arguments = array();
		if($this->operands) {
			foreach($this->operands as $operandToken) {
				$arguments[] = $operandToken->evaluate($context);
			}
		}

		return $this->function->evaluate($context, $this->arity, $arguments);
	}
	/**
	 * @return boolean
	 */
	public function isClosed() {
		return $this->closed;
	}
	public function close() {
		$this->closed = true;
	}
}
