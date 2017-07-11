<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.2
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes\functions;

use figdice\classes\Context;
use figdice\exceptions\FunctionCallException;
use figdice\FigFunction;

class Function_format_timestamp implements FigFunction {
    /**
     * Function's arguments:
     *  timestamp, format [, locale]
     *
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     *
     * @return string
     * @throws FunctionCallException
     */
	public function evaluate(Context $context, $arity, $arguments) {
		if($arity < 2) {
            throw new FunctionCallException('format_timestamp', 'Expected 2 arguments, ' . $arity . ' received.',
                $context->getFilename(),
                $context->tag->getLineNumber());
        }

		$timestamp = $arguments[0];
		$format = $arguments[1];

		$locale = isset($arguments[2]) ? $arguments[2] : 'fr';

		date_default_timezone_set('Europe/Paris');
		$oldLocale = setlocale(LC_TIME, $locale);
		if($timestamp == '') {
			$result = date($format);
		} else {
			$result = date($format, $timestamp);
		}

		//restore locale
		setlocale(LC_TIME, $oldLocale);
		return $result;
	}
}
