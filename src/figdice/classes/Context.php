<?php
namespace figdice\classes;

use figdice\exceptions\DictionaryEntryNotFoundException;
use figdice\exceptions\DictionaryNotFoundException;
use figdice\View;

class Context
{
    /** @var ViewElementTag */
    public $tag;
    /** @var string */
    public $figNamespace;

    /** @var bool[] used by the self-rendering walk tag when calling render on itself. */
    private $bypassWalk = [ false ];

    /** @var View */
    public $view;

    /** @var bool[] */
    private $doNotRenderFigParams = [ false ];


    /** @var ViewElementTag[] stack of elements being rendered while descending the tree */
    private $breadcrumb = [];

    /** @var array stack of filenames being rendered, along the nested includes */
    private $filenames = [];

    /** @var array stack of fig namespaces being rendered, along the nested includes */
    private $namespaces = [];

    /** @var Iteration[] stack of nested iterations */
    private $iterations = [];

    /** @var Dictionary[] */
    private $dictionaries = [];

    public function __construct(View $view)
    {
        $this->view = $view;
        $this->filenames = [ $view->getFilename() ];
        $this->pushIteration(new Iteration(0));
        $this->pushInclude($view->getFilename(), $view->figNamespace);

    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    public function pushDoNotRenderFigParams()
    {
        array_push($this->doNotRenderFigParams, true);
    }
    public function popDoNotRenderFigParams()
    {
        array_pop($this->doNotRenderFigParams);
    }
    public function isDoNotRenderFigParams()
    {
        return $this->doNotRenderFigParams[count($this->doNotRenderFigParams) - 1];
    }

    public function isBypassWalk()
    {
        return $this->bypassWalk[count($this->bypassWalk) - 1];
    }

    public function pushBypassWalk()
    {
        array_push($this->bypassWalk, true);
    }

    public function popBypassWalk()
    {
        array_pop($this->bypassWalk);
    }

    /**
     * Returns the data structure
     * behind the specified name.
     * Looks first in the local variables,
     * then in the data context of the element.
     *
     * @param string $name
     * @return mixed
     */
    public function getData($name) {
        //Treat plain names
        return $this->view->fetchData($name);
    }

    public function pushTag(ViewElementTag $tag)
    {
        array_push($this->breadcrumb, $tag);
        $this->tag = $tag;
    }
    public function popTag()
    {
        $this->tag = array_pop($this->breadcrumb);
    }

    public function getFilename()
    {
        return $this->filenames[count($this->filenames) - 1];
    }


    /**
     * Attaches the specified dictionary under specified name, potentially overwriting it if already present.
     * If name is the empty string, then the dictionary is anonymous.
     * Caution: this is a simplified mechanism as compared to the previous versions of FigDice, where
     * there was some sort of nesting hierarchy with dictionaries along the template inclusions.
     * The dictionaries are now global.
     * and cannot override an already loaded dictionary.
     * @param Dictionary $dictionary
     * @param string $name
     */
    public function addDictionary(Dictionary $dictionary, $name) {
        if($name) {
            $this->dictionaries[$name] = $dictionary;
        }

        //Anonymus dictionary:
        else {
            //Prepend the array of dictionaries with the new one,
            //so that it's quicker to search during the translating phase.
            array_unshift($this->dictionaries, $dictionary);
        }
    }


    /**
     * @param $name
     * @return Dictionary
     */
    public function getDictionary($name)
    {
        //TODO: handle error if name not found.
        if (array_key_exists($name, $this->dictionaries))
            return $this->dictionaries[$name];
        return null;
    }



    /**
     * If a dictionary name is specified, performs the lookup only in this one.
     * If the dictionary name is not in the perimeter of the current file,
     * bubbles up the translation request if a parent file exists.
     * Finally throws DictionaryNotFoundException if the dictionary name is not
     * found anywhere in the hierarchy.
     *
     * If the entry is not found in the current file's named dictionary,
     * throws DictionaryEntryNotFoundException.
     *
     * If no dictionary name parameter is specified, performs the lookup in every dictionary attached
     * to the current FIG file, and only if not found in any, bubbles up the request.
     *
     * @param $key
     * @param string $dictionaryName
     * @return string
     * @throws DictionaryEntryNotFoundException
     * @throws DictionaryNotFoundException
     */
    public function translate($key, $dictionaryName = null) {
        //If a dictionary name is specified,
        if(null !== $dictionaryName) {
            // Use the nearest dictionary by this name,
            // in current file upwards.
            $dictionary = $this->getDictionary($dictionaryName);
            if (null == $dictionary) {
                throw new DictionaryNotFoundException($dictionaryName, $this->getFilename(), $this->tag->getLineNumber());
            }
            try {
                return $dictionary->translate($key);
            } catch (DictionaryEntryNotFoundException $ex) {
                $ex->setDictionaryName($dictionaryName);
                $ex->setTemplateFile($this->getFilename(), $this->tag->getLineNumber());
                throw $ex;
            }
        }

        //Walk the array of dictionaries, to try the lookup in all of them.
        if(count($this->dictionaries)) {
            foreach($this->dictionaries as $dictionary) {
                try {
                    return $dictionary->translate($key);
                } catch(DictionaryEntryNotFoundException $ex) {
                    // It is perfectly legitimate to not find a key in the first few registered dictionaries.
                    // But if in the end, we still didn't find a translation, it will be time to
                    // throw the exception.
                }
            }
        }

        throw new DictionaryEntryNotFoundException($key, $this->getFilename(), $this->tag->getLineNumber());
    }

    /**
     * Returns the Iteration object to which the current tag
     * is related, if applies.
     *
     * @return Iteration
     */
    public function getIteration()
    {
        return $this->iterations[count($this->iterations) - 1];
    }
    public function pushIteration(Iteration $iteration)
    {
        array_push($this->iterations, $iteration);
    }
    public function popIteration()
    {
        array_pop($this->iterations);
    }

    public function popInclude()
    {
        array_pop($this->filenames);
        $this->figNamespace = $this->namespaces[count($this->namespaces) - 1];
    }

    /**
     * @param string $filename
     * @param string $figNamespace
     */
    public function pushInclude($filename, $figNamespace)
    {
        $this->figNamespace = $figNamespace;
        array_push($this->namespaces, $figNamespace);
        array_push($this->filenames, $filename);
    }
}
