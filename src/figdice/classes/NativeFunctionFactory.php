<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
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

namespace figdice\classes;

use figdice\FigFunction;
use figdice\FunctionFactory;

class NativeFunctionFactory extends FunctionFactory {
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Returns an instance of the Function class that
	 * handles the requested function.
	 *
	 * @param string $funcName
	 * @return FigFunction|null
	 */
	public function create($funcName) {
		
		$dirname = dirname(__FILE__).'/functions';
		$filename = "$dirname/Function_$funcName.php";
		if( !file_exists($filename) )
			return null;


		require_once "$dirname/Function_$funcName.php";

		$funcClassName = '\\figdice\\classes\\functions\\Function_'.$funcName;

		$reflection = new \ReflectionClass($funcClassName);
		/** @var FigFunction $function */
		$function = $reflection->newInstance();
		return $function;
	}
}