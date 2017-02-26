<?php
namespace figdice\classes;

use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\RequiredAttributeParsingException;

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
            throw new RequiredAttributeParsingException($this->getTagName(),
                $this->xmlLineNumber,
                'Missing "file" attribute for '.$this->getTagName().' tag (' . $this->xmlLineNumber . ')');
        }

    }

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
