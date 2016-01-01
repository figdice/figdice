<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3
 * @package FigDice
 */
namespace figdice\classes;

class Plug {
	/**
	 * @var ViewElementTag
	 */
	private $tag;
	/**
	 * @var string
	 */
	private $renderedString;
	/**
	 * @var bool
	 */
	private $append;

	public function __construct(ViewElementTag $plugTag, $renderedString = null, $isAppend = false) {
		$this->tag = $plugTag;
		$this->renderedString = $renderedString;
		$this->append = $isAppend;
	}

	/**
	 * @return ViewElementTag
	 */
	public function getTag()
	{
		return $this->tag;
	}

	/**
	 * @return null|string
	 */
	public function getRenderedString()
	{
		return $this->renderedString;
	}

	public function isAppend()
	{
		return $this->append;
	}
}
