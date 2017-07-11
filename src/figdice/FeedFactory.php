<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
 * @package FigDice
 *
 * This file is part of FigDice.
 */

namespace figdice;

interface FeedFactory {
	/**
	 * Called by the ViewElementTag::fig_feed method,
	 * to instanciate a feed by its class name.
	 *
	 * @param string $className
	 * @param array $attributes associative array of the extended parameters
	 * @return Feed Returns null if factory does not handle specified class.
	 */
	public function create($className, array $attributes);

}
