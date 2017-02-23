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
	 * @var ViewElement
	 */
	public $previousSibling;
	/**
	 * @var ViewElement
	 */
	public $nextSibling;

	/**
	 * The line in XML file where this element begins.
	 * @var int
	 */
	public $xmlLineNumber;


	/**
	 * Constructor
	 */
	public function __construct() {
		$this->outputBuffer = null;
		$this->autoclose = true;
		$this->parent = null;
		$this->previousSibling = null;
	}


    /**
     * Evaluate the XPath-like expression
     * on the data object associated to the view.
     *
     * @param Context $context
     * @param string $expression
     * @return string
     */
	public function evaluate(Context $context, $expression) {
		if(is_numeric($expression)) {
			$expression = (string)$expression;
		}
		if(! isset($context->view->lexers[$expression]) ) {
			$lexer = new Lexer($expression);
			$context->view->lexers[$expression] = & $lexer;
			$lexer->parse($this);
		}
		else {
			$lexer = & $context->view->lexers[$expression];
		}

		$result = $lexer->evaluate($context);
		return $result;
	}

	/**
	 * The line on which the element is found.
	 * @return int
	 */
	public function getLineNumber() {
		return $this->xmlLineNumber;
	}

    /**
     * @param Context $context
     * @return string
     */
	abstract public function render(Context $context);
}