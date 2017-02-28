<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.2
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
        $this->file = $filename;
        return $this;
    }
}