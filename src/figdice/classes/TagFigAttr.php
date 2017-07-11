<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes;

use figdice\exceptions\RequiredAttributeException;

class TagFigAttr extends TagFig {
	const TAGNAME = 'attr';

    /**
     * fig:attr
     * Add to the parent tag the given attribute. Do not render.
     *
     * @param Context $context
     *
     * @return string
     * @throws \Exception
     */
	public function doSpecific(Context $context)
    {
        if(!isset($this->attributes['name'])) {
            //name is a required attribute for fig:attr.
            throw new RequiredAttributeException($this->name, $this->xmlLineNumber, 'name');
        }
        //flag attribute
        // Usage: <tag><fig:attr name="ng-app" flag="true" />  will render as flag <tag ng-app> at tag level, without a value.
        if(isset($this->attributes['flag']) && $this->evaluate($context, $this->attributes['flag'])) {
            $context->setParentRuntimeAttribute($this->attributes['name'], new Flag());
        }
        else {
            if ($this->hasAttribute('value')) {
                $value = $this->evaluate($context, $this->attributes['value']);
                if (is_string($value)) {
                    $value = htmlspecialchars($value);
                }
                $context->setParentRuntimeAttribute($this->attributes['name'],  $value);
            } else {
                $value = '';
                /**
                 * @var ViewElement
                 */
                $child = null;
                foreach ($this->children as $child) {
                    $renderChild = $child->render($context);
                    if ($renderChild === false) {
                        throw new \Exception();
                    }
                    $value .= $renderChild;
                }
                //An XML attribute should not span accross several lines.
                $value = trim(preg_replace("#[\n\r\t]+#", ' ', $value));
                $context->setParentRuntimeAttribute($this->attributes['name'], $value);
            }
        }
        // No more rendering of the inner contents. Job is done, move to next sibling by
        // returning (non-null) empty string
        return '';
    }
}
