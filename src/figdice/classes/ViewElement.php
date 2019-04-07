<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
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
	}


    /**
     * Evaluate the XPath-like expression
     * on the data object associated to the view.
     *
     * @param Context $context
     * @param string $expression
     * @return string
     * @throws \figdice\exceptions\LexerSyntaxErrorException
     * @throws \figdice\exceptions\LexerUnbalancedParenthesesException
     * @throws \figdice\exceptions\LexerUnexpectedCharException
     */
	public function evaluate(Context $context, $expression) {
		if(is_numeric($expression)) {
			$expression = (string)$expression;
		}
		if(! isset($context->view->lexers[$expression]) ) {
			$lexer = new Lexer($expression);
			$context->view->lexers[$expression] = & $lexer;
			$lexer->parse($context);
		} else {
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

    abstract public function appendCDataSibling($cdata);
}
