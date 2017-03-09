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

class TagFigMount extends TagFig
{
	const TAGNAME = 'mount';

	private $mountTarget;
	private $value;

    public function setAttributes($figNamespace, array $attributes)
    {
        // We don't call the parent version, which does extraneous work of resolving conds and walks etc.,
        // whereas we just need to check existence of class attribute.
        // A feed tag is oblivious to other control directives.

        $this->attributes = $attributes;

        $this->mountTarget = $this->getAttribute('target', null);
        $this->value = $this->getAttribute('value', null);

        unset($this->attributes['target']);
        unset($this->attributes['value']);

        if(null === $this->mountTarget) {
            throw new RequiredAttributeException($this->getTagName(),
                $this->xmlLineNumber,
                'target');
        }
    }

	public function render(Context $context) {
        $this->fig_mount($context);
        return '';
    }
    private function fig_mount(Context $context) {
        //When an explicit value="" attribute exists, use its contents as a Lex expression to evaluate.
        if(null !== $this->value) {
            $value = $this->evaluate($context, $this->value);
        }
        //Otherwise, no value attribute: then we render the inner contents of the fig:mount into the target variable.
        else {
            $context->pushDoNotRenderFigParams();
            $value = $this->renderChildren($context);
            $context->popDoNotRenderFigParams();
        }

        $context->view->mount($this->mountTarget, $value);
    }

    public function serialize()
    {
        return serialize([
            'target' => $this->mountTarget,
            'value' => $this->value,
            'tree' => $this->children
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->mountTarget = $data['target'];
        $this->value = $data['value'];
        $this->children = $data['tree'];
    }
}
