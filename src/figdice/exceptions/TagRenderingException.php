<?php
namespace figdice\exceptions;

use Exception;

/**
 * Class TagRenderingException
 * This exception is raised internally, when parsing the tags.
 * The View is supposed to catch it, and enrich it with a message and the filename.
 */
class TagRenderingException extends \Exception
{
    private $tag;
    /**
     * RequiredAttributeParsingException constructor.
     * @param string $tag
     * @param int $line
     * @param string $message
     */
    public function __construct($tag, $line, $message)
    {
        parent::__construct($message);
        $this->tag = $tag;
        $this->line = $line;
    }
    public function getTag()
    {
        return $this->tag;
    }
}