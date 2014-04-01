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

namespace figdice\exceptions;

class DictionaryDuplicateKeyException extends \Exception {

	private $filename;
	private $key;

	public function __construct($filename, $key) {
		parent::__construct();
		$this->filename = $filename;
		$this->key = $key;
	}
	/**
	 * The filename of the dictionary where the key was defined more than once.
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}
	/**
	 * The key which was defined more than once in the dictionary file.
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}
}
