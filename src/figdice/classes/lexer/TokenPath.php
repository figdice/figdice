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

namespace figdice\classes\lexer;

use \figdice\classes\ViewElementTag;

class TokenPath extends Token {
	/**
	 * @var array
	 */
	private $path;

	/**
	 * @param PathElement $pathElement
	 */
	public function __construct($pathElement) {
		parent::__construct();
		if(null != $pathElement)
			$this->path = array($pathElement);
	}

	public function appendElement($pathElement) {
		if( (null == $pathElement) && (count($this->path) == 0) ) {
			$this->path[] = new PathElementRoot();
		}
		else {
			$this->path[] = $pathElement;
		}
	}

	/**
	 * @param ViewElement $viewElement
	 * @return mixed
	 */
	public function evaluate(ViewElementTag $viewElement) {

		if(0 == ($count = count($this->path))) {
			return null;
		}

		$data = null;

		for($i = 0; $i < $count; ++$i) {

			if($this->path[$i] instanceof Token) {
				$symbolName = $this->path[$i]->evaluate($viewElement);
			}
			else if($this->path[$i] instanceof PathElementRoot) {
				$symbolName = '/';
			}
			else if($this->path[$i] instanceof PathElementCurrent) {
				$symbolName = '.';				 
			}
			else if($this->path[$i] instanceof PathElementParent) {
				$symbolName = '..';
			}
			else {
				$symbolName = $this->path[$i];
			}

			//First iteration of this loop:
			//anchor the path research to the point of the universe
			//which the first-level path element refers to.
			if($data === null) {
				$data = $viewElement->getData($symbolName);
				if( $data === null )
					break;
			}

			//If data is an Object,
			//we can try to call getXxx() on the object, where Xxx is the symbolName with a capital letter.
			else if(is_object($data)) {
				$getter = 'get' . strtoupper($symbolName[0]) . substr($symbolName, 1);
				if(method_exists($data, $getter)) {
					$data = $data->$getter();
				}
				//If the getter method was not found in the object,
				//try accessing the symbol as a public attribute of the object.
				else if(array_key_exists($symbolName, get_object_vars($data))) {
					$data = $data->$symbolName;
				}
				else {
					$data = null;
					break;
				}
			}

			//TODO: Undetermined behaviour if path contains a Dot somewhere in the middle...
				
			//Continue browsing: we try to fetch a sub-key
			//of a scalar value. Nothing to return!
			else if(! is_array($data) && ! is_object($data)) {
				return null;
			}

			//Continue browsing: we are able to find the sub-key
			//of the current data. Let's continue with it.
			else if(array_key_exists($symbolName, $data)) {
				$data = $data[$symbolName];
				if(null === $data) {
					return null;
				}
			}

			//Otherwise, if we identify that current data is an array
			//with a 0-key item (which means that most certainly the array
			//is sequential), then prepare a bunch of data made of the
			//symbolName subkey of every item of the sequential array.
			//This mechanism is necessary when using aggregate functions,
			//for instance: sum(/invoices/amount).
			//If /invoices is a sequential array, each item of which has
			//an 'amount' key, we must construct an array of all the 'amount'
			//values, extracted from their container.
			//The detection of 0-key for a sequential array is not proper theoretically,
			//but it's good enough for our purpose.
			else if(is_array($data) && array_key_exists(0, $data)) {
				$population = array();
				foreach($data as $item) {
					if(is_array($item) && array_key_exists($symbolName, $item)) {
						$population[] = $item[$symbolName];
					}
					else if(is_object($item) && (isset($item->$symbolName))) {
						$population[] = $item->$symbolName;
					}
				}
				$data = $population;
			}

			else {
				return null;
			}
		}
		return $data;
	}
}
