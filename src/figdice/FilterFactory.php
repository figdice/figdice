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

interface FilterFactory {
	/**
	 * Called by the ViewElementTag::applyOutputFilter method,
	 * to instanciate a filter by its class name.
	 *
	 * @param string $className
	 * @return Filter
	 */
	public function create($className);
}