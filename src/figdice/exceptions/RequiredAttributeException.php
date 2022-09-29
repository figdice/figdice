<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.2
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\exceptions;

class RequiredAttributeException extends \Exception {
	private $tagname;
	private $attr;

    /**
     * RequiredAttributeException constructor.
     * @param string $tag
     * @param int $line
     * @param string $attr
     */
	public function __construct($tag, $line, $attr) {
		parent::__construct("Missing required attribute \"$attr\" in tag \"$tag\".");
		$this->tagname = $tag;
		$this->line = $line;
		$this->attr = $attr;
	}

    /**
     * @return string
     */
    public function getTagName() {
		return $this->tagname;
	}

    /**
     * @return string
     */
    public function getAttribute() {
		return $this->attr;
	}

    /**
     * @param string $filename
     * @return $this
     */
    public function setFile($filename)
    {
        $this->file = $filename ?? '';
        return $this;
    }
}