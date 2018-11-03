<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2018, Gabriel Zerbib.
 * @version 3.0
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes\tags;

use figdice\classes\Context;
use figdice\classes\TagFig;
use figdice\exceptions\FeedClassNotFoundException;
use figdice\exceptions\RequiredAttributeException;

class TagFigFeed extends TagFig {
	const TAGNAME = 'feed';

	private $feedClass = null;

    /**
     * @param Context $context
     *
     * @return string
     * @throws FeedClassNotFoundException
     */
    public function render(Context $context)
    {
        $this->fig_feed($context);
        return '';
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
        // A feed tag is oblivious to other control directives.

        $this->attributes = $attributes;

        $this->feedClass = isset($this->attributes['class']) ? $this->attributes['class'] : null;
        if(null === $this->feedClass) {
            throw new RequiredAttributeException(
                $this->getTagName(),
                $this->xmlLineNumber,
                'class'
            );
        }

    }

    /**
     * Process <fig:feed> tag.
     * This tag accepts the following attributes:
     *  - class = the name of the Feed class to instanciate and run.
     *  - target = the mount point in the global universe.
     *
     * @param Context $context
     * @throws FeedClassNotFoundException
     */
    private function fig_feed(Context $context) {

        //Set the parameters for the feed class:
        //the parameters are an assoc array made of the
        //scalar attributes of the fig:feed tag other than fig:* and
        //class and target attributes.
        $feedParameters = array();
        foreach($this->attributes as $attribName=>$attribText) {
            if( (! $context->view->isFigPrefix($attribName)) &&
                ($attribName != 'class') && ($attribName != 'target') ) {
                $feedParameters[$attribName] = $this->evaluate($context, $attribText);
            }
        }

        //TODO: catch exception, to enrich with fig xml file+line, and rethrow.
        $feedInstance = $context->view->createFeed($this->feedClass, $feedParameters);

        //At this point the feed instance must be created.
        //If not, there was no factory to handle its loading.
        if(! $feedInstance) {
            throw new FeedClassNotFoundException($this->feedClass, null, $this->xmlLineNumber);
        }

        //It is possible to simply invoke a Feed class and
        //discard its result, by not defining a target to the tag.
        $mountPoint = null;
        if(isset($this->attributes['target'])) {
            $mountPoint = $this->attributes['target'];
        }


        $feedInstance->setParameters($feedParameters);

        // The run method of the Feed might throw a FeedRuntimeException...
        // It means that the problem encountered is severe enough, for the Feed to
        // request that the View rendering should stop.
        // In this case, the controller is responsible for treating accordingly.
        $subUniverse = $feedInstance->run();

        if($mountPoint !== null) {
            $context->view->mount($mountPoint, $subUniverse);
        }

    }

    public function serialize()
    {
        return serialize([
            'class' => $this->feedClass,
            'attr' => $this->attributes
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->feedClass = $data['class'];
        $this->attributes = $data['attr'];
    }
}