<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
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

use figdice\exceptions\DictionaryEntryNotFoundException;
use figdice\exceptions\DictionaryNotFoundException;

class File {
	/**
	 * Fully qualified XML file name
	 * @var string
	 */
	private $filename;
	/**
	 * The file's parent file (the file where the fig:include tag reside)
	 * @var File
	 */
	private $parentFile;

	/**
	 * Stack of Dictionary objects
	 * @var array of Dictionary
	 */
	private $dictionaries;

	/**
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	public function __construct($filename, File & $parentFile = null) {
		$this->filename = $filename;
		$this->parentFile = $parentFile;
		$this->dictionaries = array();
	}

	/**
	 * @return File
	 */
	public function getParent() {
		return $this->parentFile;
	}

	/**
	 * In this method, we try to add the named dictionary to current file,
	 * but do not overwrite. If dictionary by same name exists, we return false.
	 * @param Dictionary & $dictionary
	 * @param string $name
	 * @return boolean
	 */
	private function tentativeAddDictionary(Dictionary & $dictionary, $name) {
		//Do not overwrite! Stop the recursion as soon as a dictionary by same name exists up in the hierarchy.
		if(array_key_exists($name, $this->dictionaries)) {
			//Returning false will cause the previous call in the recursion to store it in place.
			return false;
		}
		//Root file: store in place.
		if(! $this->getParent()) {
			$this->dictionaries[$name] = & $dictionary;
			return true;
		}
		if(! $this->getParent()->tentativeAddDictionary($dictionary, $name)) {
			$this->dictionaries[$name] = & $dictionary;
			return true;
		}
    return false;
	}


	/**
	 * If a dictionary name is specified, performs the lookup only in this one.
	 * If the dictionary name is not in the perimeter of the current file,
	 * bubbles up the translation request if a parent file exists.
	 * Finally throws DictionaryNotFoundException if the dictionary name is not
	 * found anywhere in the hierarchy.
	 * 
	 * If the entry is not found in the current file's named dictionary,
	 * throws DictionaryEntryNotFoundException.
	 * 
	 * If no dictionary name parameter is specified, performs the lookup in every dictionary attached
	 * to the current FIG file, and only if not found in any, bubbles up the request.
	 * 
	 * @param $key
	 * @param string $dictionaryName
	 * @return string
	 * @throws DictionaryEntryNotFoundException
	 * @throws DictionaryNotFoundException
	 */
	public function translate($key, $dictionaryName = null, $xmlLineNumber = null) {
		//If a dictionary name is specified,
		if(null !== $dictionaryName) {
			// Use the nearest dictionary by this name,
			// in current file upwards.
			$dictionary = $this->getDictionary($dictionaryName);
			if (null == $dictionary) {
				throw new DictionaryNotFoundException($dictionaryName, $this, $xmlLineNumber);
			}
      try {
        return $dictionary->translate($key);
      } catch (DictionaryEntryNotFoundException $ex) {
        $ex->setDictionaryName($dictionaryName);
        $ex->setTemplateFile($this->filename, $xmlLineNumber);
        throw $ex;
      }
		}

		//Walk the array of dictionaries, to try the lookup in all of them.
		if(count($this->dictionaries)) {
			foreach($this->dictionaries as $dictionary) {
				try {
					return $dictionary->translate($key);
				} catch(DictionaryEntryNotFoundException $ex) {
          // It is perfectly legitimate to not find a key in the first few registered dictionaries.
          // But if in the end, we still didn't find a translation, it will be time to
          // throw the exception.
				}
			}
		}
		if(null == $this->parentFile) {
			throw new DictionaryEntryNotFoundException($key, $this->filename, $xmlLineNumber);
		}
		return $this->parentFile->translate($key, $dictionaryName);
	}


}
