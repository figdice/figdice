<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
 * @package FigDice
 *
 * This file is part of FigDice.
 */

namespace figdice;

/**
 * The FunctionFactory is responsible for instantiating your custom 
 * FigFunction objects,
 * when the Lexer expressions invoke your functions.
 * Each FunctionFactory is in charge with a number of functions,
 * identified by name.
 * You may register any number FunctionFactory instances.
 * Whenever a function is invoked in an Expression, the View interrogates each
 * FunctionFactory, in the order they were registered, for the first one which
 * is able to construct the corresponding FigFunction instance.
 * Your factory must return null if it is not able to instantiate the requested
 * function.
 */
abstract class FunctionFactory {
	/**
	 * Array of FigFunction instances
	 * @var array
	 */
	private static $functions = array();

	public function __construct() {
	}

	/**
	 * Returns the instance of the Function class that
	 * handles the requested function.
	 * If it was not loaded yet, tries to instanciate by
	 * calling the overriden create method.
	 * Returns null if the factory could not produce an instance.
	 *
	 * @param string $funcName
	 * @return FigFunction
	 */
	public final function lookup($funcName) {
		if(isset(self::$functions[$funcName])) {
			return self::$functions[$funcName];
		}
		//The assign-and-return is acceptable because isset(null) returns false.
		return (self::$functions[$funcName] = $this->create($funcName));
	}
	/**
	 * Returns an instance of the Function class that
	 * handles the requested function.
	 * The framework will not call your create function more than once per
	 * function name, per View (and all its sub-templates). A caching mechanism
	 * holds your instance across multiple invocations of the function.
	 * You MUST return null if your FunctionFactory does not handle the 
	 * specified function name.
	 *
	 * @param string $funcName
	 * @return FigFunction
	 */
	abstract protected function create($funcName);
}