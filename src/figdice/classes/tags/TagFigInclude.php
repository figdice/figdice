<?php
namespace figdice\classes\tags;

use figdice\classes\Context;
use figdice\classes\TagFig;
use figdice\classes\ViewElementTag;
use figdice\exceptions\RequiredAttributeException;
use figdice\View;

class TagFigInclude extends TagFig
{
    const TAGNAME = 'include';

    /** @var string */
    private $includedFile;

    /**
     * @param Context $context
     *
     * @return string
     * @throws RequiredAttributeException
     * @throws \figdice\exceptions\FileNotFoundException
     * @throws \figdice\exceptions\RenderingException
     * @throws \figdice\exceptions\XMLParsingException
     */
    public function render(Context $context) {
        return $this->fig_include($context);
    }


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
        // An include tag is oblivious to other control directives.

        $this->includedFile = isset($attributes['file']) ? $attributes['file'] : null;
        if(null === $this->includedFile) {
            throw new RequiredAttributeException(
                $this->getTagName(),
                $this->xmlLineNumber,
                'file'
            );
        }

    }

    /**
     * Creates a sub-view object, invokes its parsing phase,
     * and renders it as the child of the current tag.
     *
     * @param Context $context
     *
     * @return string or false
     * @throws RequiredAttributeException
     * @throws \figdice\exceptions\FileNotFoundException
     * @throws \figdice\exceptions\RenderingException
     * @throws \figdice\exceptions\XMLParsingException
     */
    private function fig_include(Context $context) {

        $file = $this->includedFile;

        $realFilename = dirname($context->getFilename()).'/'.$file;
        //Create a sub-view, attached to the current element.
        $view = new View();

        if ($context->view->getCachePath() && $context->view->getTemplatesRoot()) {
            $view->setCachePath($context->view->getCachePath(), $context->view->getTemplatesRoot());
        }
        $view->loadFile($realFilename);


        //Parse the subview (build its own tree).
        $context->pushInclude($realFilename, $view->figNamespace);

        $view->parse();

        // If the included template specifies a doctype, use it globally for our context.
        if ($view->getRootNode() instanceof ViewElementTag) {
            $doctype = $view->getRootNode()->getAttribute($context->figNamespace . 'doctype');
            if ($doctype) {
                $context->setDoctype($doctype);
            }
        }


        $result = $view->getRootNode()->render($context);

        $context->popInclude();
        return $result;
    }

    public function serialize()
    {
        // This is all there is to a fig:cdata tag!
        return serialize($this->includedFile);
    }
    public function unserialize($serialized)
    {
        $this->includedFile = unserialize($serialized);
    }
}
