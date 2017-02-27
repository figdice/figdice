<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @package FigDice
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
     * @param mixed $key
     */
	public function iterate($key) {
		++ $this->position;
        $this->key = $key;
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
