<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2017, Gabriel Zerbib.
 * @version 2.5
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 * FigDice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * FigDice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FigDice.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace figdice\classes;

use figdice\exceptions\DictionaryDuplicateKeyException;
use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\RequiredAttributeException;

class TagFigDictionary extends ViewElementTag {
	const TAGNAME = 'dictionary';

	private $dicFile;

	public function __construct($name, $xmlLineNumber) {
		parent::__construct($name, $xmlLineNumber);
	}

    public function setAttributes($figNamespace, array $attributes)
    {
        // We don't call the parent version, which does extraneous work of resolving conds and walks etc.,
        // whereas we just need to check existence of class attribute.
        // A feed tag is oblivious to other control directives.

        $this->attributes = $attributes;

        $this->dicFile = isset($this->attributes['file']) ? $this->attributes['file'] : null;

        if(null === $this->dicFile) {
            throw new RequiredAttributeException($this->getTagName(),
                $this->getCurrentFile()->getFilename(),
                $this->xmlLineNumber,
                'Missing "file" attribute for '.$this->getTagName().' tag, in ' . $this->getCurrentFile()->getFilename() . '(' . $this->xmlLineNumber . ')');
        }

    }

	public function render(Context $context) {
        return $this->fig_dictionary($context);
    }


    /**
     * Loads a language XML file, to be used within the current view.
     *
     * If a Temp path was specified in the View,
     * we try to compile (serialize) the XML key-value collection and store
     * the serialized form in a 'Dictionary/(langcode)' subfolder of the temp path.
     */
    private function fig_dictionary(Context $context) {
        //If a @source attribute is specified,
        //it means that when the target (view's language) is the same as @source,
        //then don't bother loading dictionary file, nor translating: just render the tag's children.

        $file = $this->dicFile;
        $filename = $context->view->getTranslationPath() . '/' . $context->view->getLanguage() . '/' . $file;

        $name = $this->getAttribute('name', null);
        $source = $this->getAttribute('source', null);
        $dictionary = new Dictionary($filename, $source);


        if ( ($context->view->getLanguage() == '') || ($source == $context->view->getLanguage()) ) {
            // If the current View does not specify a Language,
            // or if the dictionary to load is same language as View,
            // let's not care about i18n.
            // We will activate i18n only if the dictionary explicitly specifies a source,
            // which means that we cannot simply rely on contents of the fig:trans tags.
            // However, we still need to hook the Dictionary object as a placeholder,
            // so that subsequent trans tag for the given dic name and source will
            // simply render their contents.
            $this->getCurrentFile()->addDictionary($dictionary, $name);
            return '';
        }

        //TODO: Please optimize here: cache the realpath of the loaded dictionaries,
        //so as not to re-load an already loaded dictionary in same View hierarchy.


        try {
            //Determine whether this dictionary was pre-compiled:
            if($context->view->getTempPath()) {
                $tmpFile = $context->getView()->getTempPath() . '/' . 'Dictionary' . '/' . $context->getView()->getLanguage() . '/' . $file . '.php';
                //If the tmp file already exists,
                if(file_exists($tmpFile)) {
                    //but is older than the source file,
                    if(filemtime($tmpFile) < filemtime($filename)) {
                        Dictionary::compile($filename, $tmpFile);
                    }
                }
                else {
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
                ', source=' . $this->getCurrentFilename(),
                $this->getCurrentFilename() );
        } catch(DictionaryDuplicateKeyException $ddkex) {
            $this->getLogger()->error('Duplicate key: "' . $ddkex->getKey() . '" in dictionary: ' . $ddkex->getFilename());
        }


        //Hook the dictionary to the current file.
        //(in fact this will bubble up the message as high as possible, ie:
        //to the highest parent which does not bear a dictionary of same name)
        $context->addDictionary($dictionary, $name);
        return '';
    }
}
