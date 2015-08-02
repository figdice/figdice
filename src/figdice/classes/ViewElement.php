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

use figdice\View;
use figdice\classes\lexer\Lexer;

/**
 * Node element attached to a View object.
 *
 */
abstract class ViewElement {
	public $outputBuffer;


	/**
	 * Indicates whether the XML element is to be rendered as self-closed (in case it has no content and is not muted).
	 * Example:
	 * <code>
	 *   <br fig:auto="true" />
	 * </code>
	 * will ensure to render:
	 * <code>
	 *   <br />
	 * </code>
	 * instead of:
	 * <code>
	 *   <br></br>
	 * </code>
	 *
	 * @var boolean
	 */
	public $autoclose;
	/**
	 * @var ViewElementTag
	 */
	public $parent;
	/**
	 * Indicates the index of this object
	 * in the $children array of $parent object.
	 *
	 * @var integer
	 */
	public $childOffset;

	/**
	 * @var ViewElement
	 */
	public $previousSibling;
	/**
	 * @var ViewElement
	 */
	public $nextSibling;

	/**
	 * The View object which this ViewElement
	 * is attached to.
	 * @var View
	 */
	public $view;
	public $data;
	public $logger;

	/**
	 * The line in XML file where this element begins.
	 * @var int
	 */
	public $xmlLineNumber;


	/**
	 * Constructor
	 *
	 * @param View $view The View to which this node is attached.
	 */
	public function __construct(View &$view) {
		$this->outputBuffer = null;
		$this->autoclose = true;
		$this->parent = null;
		$this->previousSibling = null;
		$this->view = &$view;
	}


	/**
	 * Evaluate the XPath-like expression
	 * on the data object associated to the view.
	 *
	 * @access private
	 * @param string $expression
	 * @return string
	 */
	public function evaluate($expression) {
		if(is_numeric($expression)) {
			$expression = (string)$expression;
		}
		if(! isset($this->view->lexers[$expression]) ) {
			$lexer = new Lexer($expression);
			$this->view->lexers[$expression] = & $lexer;
			$lexer->parse($this);
		}
		else {
			$lexer = & $this->view->lexers[$expression];
		}

		$result = $lexer->evaluate($this);
		return $result;
	}

	/**
	 * Returns the data structure
	 * behind the specified name.
	 * Looks first in the local variables,
	 * then in the data context of the element.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getData($name) {
		//Treat plain names
		return $this->view->fetchData($name);
	}

	/**
	 * The line on which the element is found.
	 * @return int
	 */
	public function getLineNumber() {
		return $this->xmlLineNumber;
	}
	/**
	 * @return View
	 */
	public function &getView() {
		return $this->view;
	}
	/**
	 * @return string
	 */
	abstract public function render();
}