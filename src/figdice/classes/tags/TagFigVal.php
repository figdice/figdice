<?php
namespace figdice\classes\tags;

use figdice\classes\Context;
use figdice\classes\TagFig;

class TagFigVal extends TagFig
{
	const TAGNAME = 'val';

	private $text;

    public function setAttributes($figNamespace, array $attributes)
    {
        parent::setAttributes($figNamespace, $attributes);
        $this->text = $this->getAttribute('text');
    }

	public function doSpecific(Context $context) {
        return $this->evalAttribute($context, 'text');
    }

    public function serialize()
    {
        return serialize([
            'text' => $this->text,
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->text = $data['text'];
    }
}
