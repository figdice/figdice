<?php
namespace figdice\exceptions;

use Exception;

/**
 * Class RequiredAttributeParsingException
 * This exception is raised internally, when parsing the tags.
 * The View is supposed to catch it, and enrich it with a message and the filename.
 */
class RequiredAttributeParsingException extends \Exception
{
    private $tag;

    /**
     * RequiredAttributeParsingException constructor.
     * @param string $tag
     * @param int $line
     * @param string $attribute
     */
    public function __construct($tag, $line, $attribute)
    {
        parent::__construct();
        $this->tag = $tag;
    }
    public function getTag()
    {
        return $this->tag;
    }
}
