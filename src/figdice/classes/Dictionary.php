<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
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

use \figdice\exceptions\DictionaryEntryNotFoundException;
use \figdice\exceptions\DictionaryDuplicateKeyException;
use \figdice\exceptions\FileNotFoundException;

/**
 * The dictionary XML file must be in the form:
 * 
 * <fig:dictionary>
 * 		<entry key="...">value</entry>
 * 		...
 * </fig:dictionary>
 * 
 * where value can be any string, including nested XML,
 * including FIG-renderable directives (translation arguments may be passed as fig:param arguments, or
 * extra-attributes to the fig:trans directive, and they will be evaluated at run-time by the container.
 * @author gabriel.z
 */
class Dictionary {
	/**
	 * @var string
	 */
	private $filename;
	/**
	 * @var string
	 */
	private $source;

	/**
	 * @return string
	 */
	public function getSource()
	{
		return $this->source;
	}
	/**
	 * @var \DOMDocument
	 */
	private $domDocument;
	/**
	 * Associative array of key/value pairs,
	 * maintained internally as a cache, to avoid repeating an xpath query that
	 * was succesful earlier.
	 * @var array
	 */
	private $cache;

	public function __construct($filename, $source = null) {
		$this->filename = $filename;
		$this->source = $source;
		$this->cache = array();
	}

	/**
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function load() {
		$this->domDocument = new \DOMDocument();
		if(! file_exists($this->filename)) {
			throw new FileNotFoundException('Translation file not found.', $this->filename);
		}
		$this->domDocument->load($this->filename, LIBXML_COMPACT | LIBXML_NONET);
	}
	/**
	 * Returns the string (potentially a piece of XML) obtained for the specified key.
	 * @param $key
	 * @return string
	 * @throws DictionaryEntryNotFoundException
	 */
	public function translate($key) {

		//First: check the cache:
		if(isset($this->cache[$key])) {
			if($this->cache[$key] instanceof DictionaryEntryNotFoundException) {
				throw $this->cache[$key];
			}
			return $this->cache[$key];
		}

		//If the key could not be found in cache,
		//and we do not have a valid domDocument to parse (ie. the dictionary was
		//initialized by restore rather than load)
		//throw an Entry Not Found Exception.
		if(null == $this->domDocument) {
			throw ($this->cache[$key] = new DictionaryEntryNotFoundException($key));
		}

		$xpath = new \DOMXPath($this->domDocument);

		//Find the entry (whose key attribute is as specified):
		$domNodeList = $xpath->evaluate('/fig:dictionary/entry[@key="' . $key . '"]');

		if($domNodeList->length == 0) {
			//The translation for this key was not found in the current dictionary.
			throw ($this->cache[$key] = new DictionaryEntryNotFoundException($key));
		}

		//The Value part is the inner contents of the entry tag.
		$result = $this->domDocument->saveXML($domNodeList->item(0));
		$result = preg_replace('/^<entry[^>]+>/', '', $result);
		$result = preg_replace('/<\/entry>$/', '', $result);

		return ($this->cache[$key] = $result);
	}

	/**
	 * @param string $source Path and name of the file to compile.
	 * @param string $target
	 * @return boolean
	 * @throws DictionaryDuplicateKeyException, FileNotFoundException
	 * TODO: Perform some permission tests on the target folder, and throw relevant exceptions accordingly.
	 */
	public static function compile($source, $target) {

		if (! file_exists($source)) {
			throw new FileNotFoundException('Dictionary file not found', $source);
		}

		$entries = array();
		$domDocument = new \DOMDocument();
		$domDocument->load($source, LIBXML_COMPACT | LIBXML_NONET);
		$domNodeList = $domDocument->getElementsByTagName('entry');
		$count = $domNodeList->length;
		for($i = 0; $i < $count; ++ $i) {
			$domNode = $domNodeList->item($i);
		//The Value part is the inner contents of the entry tag.
			$value = $domDocument->saveXML($domNode);
			$value = preg_replace('/^<entry[^>]+>/', '', $value);
			$value = preg_replace('/<\/entry>$/', '', $value);
			$key = $domNode->getAttribute('key');
			if(array_key_exists($key, $entries)) {
				throw new DictionaryDuplicateKeyException($source, $key);
			}
			$entries[$key] = $value;
		}

		//Now save the assoc array.
		//If the hierarchy of folders where to store the target does not exist,
		//attempt to create it.
		if(! file_exists(dirname($target))) {
			mkdir(dirname($target), 0777, true);
		}
		$fp = fopen($target, 'w');
		fwrite($fp, serialize($entries));
		fclose($fp);

		return true;
	}

	public function restore($precompiledFile) {
		$this->cache = unserialize(file_get_contents($precompiledFile));
	}
}
