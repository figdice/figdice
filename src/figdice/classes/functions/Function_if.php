<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.4
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

namespace figdice\classes\functions;

use figdice\classes\Context;
use figdice\FigFunction;
use figdice\exceptions\FunctionCallException;

class Function_if implements FigFunction {
	public function __construct() {
	}

    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return mixed
     * @throws FunctionCallException
     */
    public function evaluate(Context $context, $arity, $arguments) {
		if ( ($arity != 2) && ($arity != 3) ) {
			throw new FunctionCallException('if', 'Expected 2 or 3 arguments, ' . $arity . ' received.',
                $context->getFilename(),
                $context->tag->getLineNumber());
		}
		// The 2-arg version simply uses empty as the "else" value.
		if ($arity == 2)
		    $arguments[2] = '';

		return ($arguments[0] ? $arguments[1] : $arguments[2]);
	}
}