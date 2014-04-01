<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
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

class Iteration {
	/**
	 * @var integer
	 */
	private $position;
	/**
	 * @var integer
	 */
	private $count;
	/**
	 * @var string
	 */
	private $key = null;

	public function __construct($count) {
		$this->count = $count;
		$this->position = 0;
	}
	
	/**
	 * To be called before each iteration,
	 * including for first element of collection.
	 */
	public function iterate($key = null) {
		++ $this->position;
		if( null !== $key) {
			$this->key = $key;
		}
		else {
			$this->key = $this->position;
		}
	}

	/**
	 * @return boolean
	 */
	public function first() {
		return $this->position == 1;
	}
	/**
	 * @return boolean
	 */
	public function last() {
		return $this->position == $this->count;
	}
	/**
	 * @return boolean
	 */
	public function even() {
		return (($this->position % 2) == 0);
	}
	/**
	 * @return boolean
	 */
	public function odd() {
		return (($this->position % 2) == 1);
	}

	/**
	 * @return integer
	 */
	public function getPosition() {
		return $this->position;
	}
	/**
	 * @return integer
	 */
	public function getCount() {
		return $this->count;
	}
	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}
}
