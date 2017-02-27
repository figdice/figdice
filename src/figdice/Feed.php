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

namespace figdice;

/**
 * The Feed is the data provider for the View. It makes the link between the data managed by 
 * the application, and the contents to serve to the client.
 *
 * When the {@link View} template renderer encounters a fig:feed tag, it instanciates an object whose class
 * is specified by the class attribute. This class must inherit from abstract class Feed.
 * Then the engine passes to the object its calling parameters, and executes its run method.
 * The result of run is added to the Universe of the view, at root level, under the name specified 
 * by the target attribute of fig:feed.
 * A new Feed object is instanciated at each call to fig:feed, even if one same class is 
 * invoked multiple times in the same View.
 */
abstract class Feed {
	

	/**
	 * Associative array of the input parameters of the feed,
	 * as passed in the call by tag <fig:feed paramN="valueN">
	 * Names 'class', 'file' and 'target' are reserved and cannot be used.
	 *
	 * @var array
	 */
	private $params = array();

	public function __construct()
    {
	}

	/**
	 * Called by ViewElementTag after instanciation of the feed class
	 * and before calling the run( ) method, in order to set the input parameters
	 * with which the feed will be able to work.
	 *
	 * @param array $params
	 */
	public function setParameters($params) {
		$this->params = $params;
	}
	function getParameterBool($paramName, $defaultValue = false) {
		if(isset($this->params[$paramName])) {
			return ($this->params[$paramName] == true);
		}
		return $defaultValue;
	}
	function getParameterInt($paramName, $defaultValue = 0) {
		if(array_key_exists($paramName, $this->params)) {
			return intval($this->params[$paramName]);
		}
		return $defaultValue;
	}
	/**
	 * @param string $paramName
	 * @param string $defaultValue
	 * @return string
	 */
	function getParameterString($paramName, $defaultValue = '') {
		if(isset($this->params[$paramName]))
			return $this->params[$paramName];
		return $defaultValue;
	}
	/**
	 * Returns a Feed parameter, leaving it untyped.
	 * Can be used to retrieve arrays, in particular.
	 *
	 * @param string $paramName
	 * @return mixed
	 */
	public function getParameter($paramName) {
		if(isset($this->params[$paramName]))
			return $this->params[$paramName];
		return null;
	}
	
	/**
	 * Your Feed instance can return any kind of result to the surrounding
	 * universe. The most common use-case are: arrays (associative or indexed) and
	 * bean-like objects (objects with a getter method for each exposed attribute),
	 * but it can really be anything, since your templates can transport any result
	 * from Feed to Feed.
	 * 
	 * @return mixed
	 */
	abstract public function run();
}
