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

use figdice\exceptions\RequiredAttributeException;

class TagFigMount extends ViewElementTag {
	const TAGNAME = 'mount';

	private $mountTarget;

	public function __construct($name, $xmlLineNumber) {
		parent::__construct($name, $xmlLineNumber);
	}

    public function setAttributes($figNamespace, array $attributes)
    {
        // We don't call the parent version, which does extraneous work of resolving conds and walks etc.,
        // whereas we just need to check existence of class attribute.
        // A feed tag is oblivious to other control directives.

        $this->attributes = $attributes;

        $this->mountTarget = isset($this->attributes['target']) ? $this->attributes['target'] : null;

        if(null === $this->mountTarget) {
            throw new RequiredAttributeException($this->getTagName(),
                $this->getCurrentFile()->getFilename(),
                $this->xmlLineNumber,
                'Missing "target" attribute for '.$this->getTagName().' tag, in ' . $this->getCurrentFile()->getFilename() . '(' . $this->xmlLineNumber . ')');
        }

    }

	public function render(Context $context) {
        $this->fig_mount($context);
        return '';
    }
    private function fig_mount(Context $context) {
        //When an explicit value="" attribute exists, use its contents as a Lex expression to evaluate.
        if($this->hasAttribute('value')) {
            $valueExpression = $this->getAttribute('value');
            $value = $this->evaluate($context, $valueExpression);
        }
        //Otherwise, no value attribute: then we render the inner contents of the fig:mount into the target variable.
        else {
            $context->pushDoNotRenderFigParams();
            $value = $this->renderChildren($context);
            $context->popDoNotRenderFigParams();
        }

        $context->view->mount($this->mountTarget, $value);
    }
}