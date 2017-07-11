<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
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

/**
 * The count function can be invoked with 0 or 1 arguments.
 * 1 arg is: a countable collection, and it returns the count.
 * 0 arg is: inside an iteration, it returns the total count of the iterating collection.
 */
class Function_count implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return int|mixed
     */
    public function evaluate(Context $context, $arity, $arguments) {

        if ($arity == 1) {
            $param = $arguments[0];
            if (is_array($param)) {
                return count($param);
            }
        } else if ($arity == 0) {
            if ($iter = $context->getIteration()) {
                return $iter->getCount();
            }
        }

		return 0;
	}
}