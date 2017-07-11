<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
 * @package FigDice
 *
 * This file is part of FigDice.
 */

namespace figdice\classes;


class FigDOMNodeList implements \Countable, \Iterator {
	/**
	 * @var \DOMNodeList
	 */
	private $domNodeList = null;
	private $index = -1;
	private $current = null;

	public function __construct(\DOMNodeList $dnl) {
		$this->domNodeList = $dnl;
	}

	public function count() {
		return $this->domNodeList->length;
	}

	public function key() {
		return $this->index;
	}

	public function current() {
		return $this->current;
	}

	public function valid() {
		return ($this->current != null);
	}
	public function rewind() {
		$this->index = -1;
		$this->next();
	}

	public function next() {
		if($this->index < $this->count() - 1) {
			$this->current = $this->domNodeList->item(++ $this->index);
		}
		else {
			$this->current = null;
		}
		return $this->current;
	}
}