<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes;

class Slot {
	/**
	 * @var string
	 */
	private $anchorString;
	private $length;

	public function __construct($anchorString) {
		$this->anchorString = $anchorString;
	}
	/**
	 * @return string
	 */
	public function getAnchorString()
	{
		return $this->anchorString;
	}
	/**
	 * @return integer
	 */
	public function getLength()
	{
		return $this->length;
	}
	/**
	 * Sets the length of the replacement contents.
	 * @param integer $length
	 */
	public function setLength($length)
	{
		$this->length = $length;
	}
}
