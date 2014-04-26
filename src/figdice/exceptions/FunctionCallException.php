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

namespace figdice\exceptions;

/**
 * In general, in FigDice, the Views should be waterproof most of the time,
 * and you should handle properly any execution errors in your Functions.
 * (such as missing arguments, database failures, filesystem errors and so on).
 * The consumer of your functions should never have to worry about errors, and 
 * it is recommended that your functions simply return an empty result 
 * in case of failure.
 * However, in some cases you may have to report a severe runtime error,
 * which should cause the normal rendering workflow to break. Use this
 * exception to indicate the rendering engine to stop. Your controller should
 * catch this exception and propose an alternate output.
 */
class FunctionCallException extends \Exception {
	private $funcName;
	public function __construct($funcName, $message, $filename, $line) {
		parent::__construct($funcName . ': ' . $message);
		$this->file = $filename;
		$this->line = $line;
	}
}
