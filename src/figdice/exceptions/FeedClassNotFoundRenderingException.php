<?php
namespace figdice\exceptions;

/**
 * Class FeedClassNotFoundRenderingException
 * This exception is raised internally, when rendering the tags.
 * The View is supposed to catch it, and enrich it with a message and the filename.
 */
class FeedClassNotFoundRenderingException extends \Exception
{
    protected $tag;
    protected $classname;

    /**
     * FeedClassNotFoundRenderingException constructor.
     * @param string $feedClassName
     * @param string $tag
     * @param int $line
     */
    public function __construct($feedClassName, $tag, $line)
    {
        parent::__construct();
        $this->line = $line;
        $this->tag = $tag;
        $this->classname = $feedClassName;
    }
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function getClassname()
    {
        return $this->classname;
    }
}
