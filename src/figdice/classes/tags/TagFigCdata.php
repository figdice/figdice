<?php
namespace figdice\classes\tags;

use figdice\classes\Context;
use figdice\classes\TagFig;
use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\RequiredAttributeException;

class TagFigCdata extends TagFig {
	const TAGNAME = 'cdata';

	private $dataFile;

    /**
     * @param $figNamespace
     * @param array $attributes
     *
     * @throws RequiredAttributeException
     */
    public function setAttributes($figNamespace, array $attributes)
    {
        // We don't call the parent version, which does extraneous work of resolving conds and walks etc.,
        // whereas we just need to check existence of class attribute.
        // A feed tag is oblivious to other control directives.


        $this->dataFile = isset($attributes['file']) ? $attributes['file'] : null;

        if(null === $this->dataFile) {
            throw new RequiredAttributeException($this->getTagName(),
                $this->xmlLineNumber,
                'file');
        }

    }

    /**
     * @param Context $context
     *
     * @return string
     * @throws FileNotFoundException
     */
    public function render(Context $context) {
        return $this->fig_cdata($context);
    }

    /**
     * Imports at the current output position
     * the contents of specified file unparsed, rendered as is.
     * @param Context $context
     * @return string
     * @throws FileNotFoundException
     */
    private function fig_cdata(Context $context) {
        $filename = $this->dataFile;
        $realfilename = dirname($context->getFilename()).'/'.$filename;
        if(! file_exists($realfilename)) {
            $message = "File not found: $filename called from: " . $context->getFilename(). '(' . $this->xmlLineNumber . ')';
            throw new FileNotFoundException($message, $filename);
        }
        $cdata = file_get_contents($realfilename);
        return $cdata;
    }

    public function serialize()
    {
        // This is all there is to a fig:cdata tag!
        return serialize($this->dataFile);
    }
    public function unserialize($serialized)
    {
        $this->dataFile = unserialize($serialized);
    }
}
