<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @version 3.2.2
 * @package FigDice
 *
 * This file is part of FigDice.
 */

namespace figdice\classes\functions;

use figdice\classes\Context;
use figdice\FigFunction;
use figdice\exceptions\FunctionCallException;

/**
 * The countif function is invoked with exactly 1 argument.
 * It is similar to count but returns empty string instead of 0.
 */
class Function_countif implements FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return int|mixed
     * @throws FunctionCallException
     */
    public function evaluate(Context $context, $arity, $arguments) {

        if ($arity == 1) {
            $param = $arguments[0];
            if (is_array($param)) {
                $count = count($param);
                return $count > 0 ? $count : '';
            }
            return '';
        }

        throw new FunctionCallException(
            'countif', 
            'Expects 1 argument.',
            $context->getFilename(),
            $context->tag->getLineNumber()
        );
    }
}
