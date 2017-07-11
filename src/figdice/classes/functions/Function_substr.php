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

class Function_substr implements FigFunction {
    /**
     * Function's arguments:
     *  timestamp, format [, locale]
     *
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return string
     * @throws FunctionCallException
     */
	public function evaluate(Context $context, $arity, $arguments) {
		if($arity <= 1) {
			throw new FunctionCallException(
				'substr', 
				'Too few arguments.',
                $context->getFilename(),
                $context->tag->getLineNumber()
			);
		}

		$string = $arguments[0];
		$start = $arguments[1];
		if(3 <= $arity) {
			$length = $arguments[2];
			return substr($string, $start ,$length);
		} else {
			return substr($string, $start);
		}
	}
}
