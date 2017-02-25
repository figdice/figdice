<?php
namespace figdice\classes;

use figdice\exceptions\DictionaryEntryNotFoundException;
use figdice\exceptions\DictionaryNotFoundException;
use figdice\View;

class Context
{
    /** @var string */
    private $doctype = null;

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

    /**
     * A stack of arrays of dictionaries.
     * In the stack, each floor corresponds to the $filenames stack, and is, itself, a map of dictionaries.
     * @var Dictionary[][] */
    private $dictionaries = [];

    /**
     * The array of named slots defined in the view.
     * A slot is a specific ViewElementTag identified
     * by a name, whose content is replaced by the content
     * of the element that has been pushed (plugged) into the slot.
     * @var Slot[]
     */
    private $slots = [];

    /**
     * Array of named elements that are used as content providers
     * to fill in slots by the same name.
     * @var Plug[]
     */
    private $plugs = [];

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

    /**
     * Set to true, the flag indicating that the current tag in tree has entered a walk loop.
     */
    public function setBypassWalk()
    {
        $this->bypassWalk[count($this->bypassWalk) - 1] = true;
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
        array_push($this->bypassWalk, false);
        $this->tag = $tag;
    }
    public function popTag()
    {
        $this->tag = array_pop($this->breadcrumb);
        array_pop($this->bypassWalk);
    }

    public function getFilename()
    {
        return $this->filenames[count($this->filenames) - 1];
    }


    /**
     * Attaches the specified dictionary under specified name.
     * A named dictionary is attached to the most outer view which does not yet contain the key (so as to
     * make it available as far away as possible after the include terminates),
     * or overwrites the current file's named dictionary if no free room found.
     * An anonymous dictionary is always added to the local file's, and never bubbles up.
     * @param Dictionary $dictionary
     * @param string $name
     */
    public function addDictionary(Dictionary $dictionary, $name) {

        $N = count($this->dictionaries);

        if($name) {
            // Try to add the named dictionary to the most ancient (outermost) view first.

            for ($i = 0; $i < $N; ++ $i) {
                // We never overwrite an existing key, except in the topmost floor (current file)
                if (! array_key_exists($name, $this->dictionaries[$i]) || ($i == $N - 1) ) {
                    $this->dictionaries[$i][$name] = $dictionary;
                }
            }
        }

        //Anonymus dictionary:
        else {
            //Prepend the array of dictionaries with the new one,
            //so that it's quicker to search during the translating phase.
            array_unshift($this->dictionaries[$N - 1], $dictionary);
        }
    }


    /**
     * @param string $name
     * @return Dictionary
     */
    public function getDictionary($name)
    {
        // Find by name, upwards. Start by the current floor of dictionaries in stack,
        // and then go back until found.
        $N = count($this->dictionaries);
        for ($i = $N - 1; $i >= 0; -- $i) {
            if (array_key_exists($name, $this->dictionaries[$i]))
                return $this->dictionaries[$i][$name];
        }

        //TODO: handle error if name not found.
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
        foreach($this->dictionaries[count($this->dictionaries) - 1] as $dictionary) {
            try {
                return $dictionary->translate($key);
            } catch(DictionaryEntryNotFoundException $ex) {
                // It is perfectly legitimate to not find a key in the first few registered dictionaries.
                // But if in the end, we still didn't find a translation, it will be time to
                // throw the exception.
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
        array_pop($this->dictionaries);
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
        array_push($this->dictionaries, []);
    }

    /**
     * This method is called by ViewElementTag objects, when processing
     * a fig:slot item.
     * @param string $slotName
     * @param Slot $slot
     */
    public function assignSlot($slotName, Slot $slot) {
        $this->slots[$slotName] = & $slot;
    }

    /**
     * @param $slotName
     * @param ViewElementTag $element
     * @param string $renderedString
     * @param bool $isAppend
     */
    public function addPlug($slotName, $renderedString = null, $isAppend = false) {
        $this->plugs[$slotName] [] = new Plug($this->tag, $renderedString, $isAppend);
    }


    /**
     * @return Slot[]
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * @param string $slotName
     * @return Plug[]|null
     */
    public function getPlugs($slotName)
    {
        return array_key_exists($slotName, $this->plugs) ? $this->plugs[$slotName] : null;
    }

    /**
     * @param string $doctype
     */
    public function setDoctype($doctype)
    {
        $this->doctype = $doctype;
    }
    public function getDoctype()
    {
        return $this->doctype;
    }
}
