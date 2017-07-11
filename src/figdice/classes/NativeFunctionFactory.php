<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.3
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
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
		if( !file_exists($filename) ) {
					return null;
		}


		require_once "$dirname/Function_$funcName.php";

		$funcClassName = '\\figdice\\classes\\functions\\Function_'.$funcName;

		$reflection = new \ReflectionClass($funcClassName);
		/** @var FigFunction $function */
		$function = $reflection->newInstance();
		return $function;
	}
}