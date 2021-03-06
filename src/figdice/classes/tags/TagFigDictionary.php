<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2017, Gabriel Zerbib.
 * @version 2.5
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes\tags;

use figdice\classes\Context;
use figdice\classes\Dictionary;
use figdice\classes\TagFig;
use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\RequiredAttributeException;

class TagFigDictionary extends TagFig {
	const TAGNAME = 'dictionary';

	private $dicFile;
	private $dicName;
	private $source;

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

        $this->dicFile = $this->getAttribute('file', null);
        $this->dicName = $this->getAttribute('name', null);
        $this->source = $this->getAttribute('source', null);

        unset($this->attributes['file']);
        unset($this->attributes['name']);
        unset($this->attributes['source']);


        if(null === $this->dicFile) {
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
     * @throws \figdice\exceptions\DictionaryDuplicateKeyException
     */
    public function render(Context $context) {
        return $this->fig_dictionary($context);
    }


    /**
     * Loads a language XML file, to be used within the current view.
     *
     * If a Temp path was specified in the View,
     * we try to compile (serialize) the XML key-value collection and store
     * the serialized form in a 'Dictionary/(langcode)' subfolder of the temp path.
     *
     * @param Context $context
     *
     * @return string
     * @throws FileNotFoundException
     * @throws \figdice\exceptions\DictionaryDuplicateKeyException
     */
    private function fig_dictionary(Context $context) {
        //If a @source attribute is specified,
        //it means that when the target (view's language) is the same as @source,
        //then don't bother loading dictionary file, nor translating: just render the tag's children.

        $file = $this->dicFile;
        $filename = $context->view->getTranslationPath() . '/' . $context->view->getLanguage() . '/' . $file;

        $dictionary = new Dictionary($filename, $this->source);


        if ( ($context->view->getLanguage() == '') || ($this->source == $context->view->getLanguage()) ) {
            // If the current View does not specify a Language,
            // or if the dictionary to load is same language as View,
            // let's not care about i18n.
            // We will activate i18n only if the dictionary explicitly specifies a source,
            // which means that we cannot simply rely on contents of the fig:trans tags.
            // However, we still need to hook the Dictionary object as a placeholder,
            // so that subsequent trans tag for the given dic name and source will
            // simply render their contents.
            $context->addDictionary($dictionary, $this->dicName);
            return '';
        }

        //TODO: Please optimize here: cache the realpath of the loaded dictionaries,
        //so as not to re-load an already loaded dictionary in same View hierarchy.


        try {
            //Determine whether this dictionary was pre-compiled:
            if($context->view->getCachePath()) {
                $tmpFile = $context->getView()->getCachePath() . '/' . 'Dictionary' . '/' . $context->getView()->getLanguage() . '/' . $file . '.php';
                //If the tmp file already exists,
                if(file_exists($tmpFile)) {
                    //but is older than the source file,
                    if(file_exists($filename) && (filemtime($tmpFile) < filemtime($filename)) ) {
                        Dictionary::compile($filename, $tmpFile);
                    }
                } else {
                    Dictionary::compile($filename, $tmpFile);
                }
                $dictionary->restore($tmpFile);
            }

            //If we don't even have a temp folder specified, load the dictionary for the first time.
            else {
                $dictionary->load();
            }
        } catch(FileNotFoundException $ex) {
            throw new FileNotFoundException('Translation file not found: file=' . $filename .
                ', language=' . $context->view->getLanguage() .
                ', source=' . $context->getFilename(),
                $context->getFilename() );
        }


        //Hook the dictionary to the current file.
        //(in fact this will bubble up the message as high as possible, ie:
        //to the highest parent which does not bear a dictionary of same name)
        $context->addDictionary($dictionary, $this->dicName);
        return '';
    }

    public function serialize()
    {
        return serialize([
            'file' => $this->dicFile,
            'name' => $this->dicName,
            'src' =>  $this->source
        ]);
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->dicFile = $data['file'];
        $this->dicName = $data['name'];
        $this->source = $data['src'];
    }
}
