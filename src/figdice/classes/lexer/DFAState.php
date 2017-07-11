<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.1
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes\lexer;

use figdice\exceptions\LexerUnexpectedCharException;

abstract class DFAState {
	/**
	 * @var boolean
	 */
	protected $closed;

	/**
	 * @var string
	 */
	protected $buffer;

	public function __construct() {
		$this->buffer = '';
		$this->closed = false;
	}


	/**
	 * @param string $string
	 */
	public function setBuffer($string)
	{
		$this->buffer = $string;
		$this->closed = false;
	}

	/**
	 * @param Lexer $lexer
	 * @param string $char
	 */
	abstract public function input(Lexer $lexer, $char);

	/**
	 * @param string $char
	 * @return boolean
	 */
	protected static function isAlpha($char)
	{
		return (
			( ($char >= 'a') && ($char <= 'z') ) ||
			( ($char >= 'A') && ($char <= 'Z') ) ||
			($char == '_')
		);
	}
	/**
	 * @param string $char
	 * @return boolean
	 */
	protected static function isAlphaNum($char) {
		return (
			self::isDigit($char) ||
			self::isAlpha($char)
		);
	}
	/**
	 * @param string $char
	 * @return boolean
	 */
	protected static function isDigit($char) {
		return ( ($char >= '0') && ($char <= '9') );
	}
	/**
	 * @param string $char
	 * @return boolean
	 */
	protected static function isBlank($char) {
		return ( ($char == ' ') || ($char == "\t") );
	}

	/**
	 * @param Lexer $lexer
	 */
	public function endOfInput($lexer) {
		$this->throwErrorWithMessage($lexer, 'Unimplemented end of input for state: ' . get_class($this));
	}

	/**
	 * @param Lexer $lexer
	 * @param string $char
	 * @throws LexerUnexpectedCharException
	 */
	protected function throwError($lexer, $char) {
		$message = 'Unexpected char: ' . $char;
		$message = get_class($this) . ': file: ' . $lexer->getViewFile() . '(' . $lexer->getViewLine() . '): ' . $message . ' in expression: ' . $lexer->getExpression();
		throw new LexerUnexpectedCharException($message, $lexer->getViewFile(), $lexer->getViewLine());
	}

    /**
     * @param Lexer $lexer
     * @param string $message
     * @throws LexerUnexpectedCharException
     */
	protected function throwErrorWithMessage($lexer, $message) {
		$message = get_class($this) . ': file: ' . $lexer->getViewFile() . '(' . $lexer->getViewLine() . '): ' . $message . ' in expression: ' . $lexer->getExpression();
		throw new LexerUnexpectedCharException($message, $lexer->getViewFile(), $lexer->getViewLine());
	}
}
