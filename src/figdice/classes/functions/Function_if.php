<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.4
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes\functions;

use figdice\classes\Context;
use figdice\FigFunction;
use figdice\exceptions\FunctionCallException;

class Function_if implements FigFunction {
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