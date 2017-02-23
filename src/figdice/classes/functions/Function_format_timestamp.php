<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.2
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
use figdice\exceptions\FunctionCallException;
use \figdice\FigFunction;
use \figdice\classes\ViewElementTag;

class Function_format_timestamp implements FigFunction {
	public function __construct() {
	}

    /**
     * Function's arguments:
     *  timestamp, format [, locale]
     *
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return string
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
