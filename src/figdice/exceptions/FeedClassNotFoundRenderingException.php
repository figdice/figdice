<?php
namespace figdice\exceptions;

/**
 * Class FeedClassNotFoundRenderingException
 * This exception is raised internally, when rendering the tags.
 * The View is supposed to catch it, and enrich it with a message and the filename.
 */
class FeedClassNotFoundRenderingException extends \Exception
{
    /**
     * FeedClassNotFoundRenderingException constructor.
     * @param string $feedClassName
     * @param int $line
     */
    public function __construct($feedClassName, $line)
    {
        parent::__construct();
    }
}
