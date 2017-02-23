<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2017, Gabriel Zerbib.
 * @version 2.5
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

use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\RequiredAttributeException;

class TagFigCdata extends ViewElementTag {
	const TAGNAME = 'cdata';

	private $dataFile;

	public function __construct($name, $xmlLineNumber) {
		parent::__construct($name, $xmlLineNumber);
	}

    public function setAttributes($figNamespace, array $attributes)
    {
        // We don't call the parent version, which does extraneous work of resolving conds and walks etc.,
        // whereas we just need to check existence of class attribute.
        // A feed tag is oblivious to other control directives.


        $this->dataFile = isset($attributes['file']) ? $attributes['file'] : null;

        if(null === $this->dataFile) {
            throw new RequiredAttributeException($this->getTagName(),
                $this->getCurrentFile()->getFilename(),
                $this->xmlLineNumber,
                'Missing "file" attribute for '.$this->getTagName().' tag, in ' . $this->getCurrentFile()->getFilename() . '(' . $this->xmlLineNumber . ')');
        }

    }

	public function render(Context $context) {
        return $this->fig_cdata($context);
    }
    /**
     * Imports at the current output position
     * the contents of specified file unparsed, rendered as is.
     * @return string
     * @throws FileNotFoundException
     */
    private function fig_cdata(Context $context) {
        $filename = $this->dataFile;
        $realfilename = dirname($context->getFilename()).'/'.$filename;
        if(! file_exists($realfilename)) {
            $message = "File not found: $filename called from: " . $this->getCurrentFilename(). '(' . $this->xmlLineNumber . ')';
            throw new FileNotFoundException($message, $filename);
        }
        $cdata = file_get_contents($realfilename);
        return $cdata;
    }
}
