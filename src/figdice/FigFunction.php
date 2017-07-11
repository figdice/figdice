<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2017, Gabriel Zerbib.
 * @version 2.5
 * @package FigDice
 *
 * This file is part of FigDice.
 */

namespace figdice;

use figdice\classes\Context;

/**
 * Your FigFunction instances are created at most once per View lifecycle.
 * If the same function is invoked several times in the template, there will
 * still be only one instance of your FigFunction object, and its evaluate
 * method is invoked each time.
 * Therefore, you can maintain instance properties (for calcualation caching
 * purposes for instance, or DB access and so on), but you cannot rely on "fresh"
 * instance in your evalute function.
 */
interface FigFunction {
    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return mixed
     */
	function evaluate(Context $context, $arity, $arguments);
}
