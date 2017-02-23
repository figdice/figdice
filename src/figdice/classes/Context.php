<?php
namespace figdice\classes;

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

    /** @var Iteration[] stack of nested iterations */
    private $iterations = [];

    public function __construct(View $view)
    {
        $this->view = $view;
        $this->filenames = [ $view->getFilename() ];
        $this->pushTag($view->getRootNode());
        $this->pushIteration(new Iteration(0));
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
     * Attaches the specified dictionary to the current file, under specified name.
     * If name is the empty string, then the dictionary is not named,
     * and cannot override an already loaded dictionary,
     * and cannot be made available to parent files.
     * To the contrary, attaching an explicitly named dictionary tries to
     * hook it first to the parent (recursively), if the parent exists and if it does not
     * already have a dictionary by same name. Then, if immediate parent has a dictionary
     * by same name, then we attach it to the file itself (thus potentially overwriting
     * another dictionary by same name in current file).
     * @param Dictionary & $dictionary
     * @param string $name
     * @return boolean Returns true if the dictionary was attached in current file or higher in hierarchy.
     */
    public function addDictionary(Dictionary & $dictionary, $name) {
        if($name) {
            //If I already have a dictionary by this name,
            //or if Root file: store in place.
            //I am only requested to overwrite.
            if ((array_key_exists($name, $this->dictionaries)) ||
                (! $this->getParent()) ) {
                $this->dictionaries[$name] = & $dictionary;
                return true;
            }

            //Otherwise, try to bubble up the event, but then do not overwrite
            //the dictionary anywhere in the parent hierarchy.
            if($this->getParent()->tentativeAddDictionary($dictionary, $name)) {
                return true;
            }

            // If we couldn't store the named dic in any file above
            // (ie every ancestor already owns a dic with same name),
            // then do store it in place.
            $this->dictionaries[$name] = & $dictionary;
            return true;
        }

        //Anonymus dictionary:
        else {
            //Prepend the array of dictionaries with the new one,
            //so that it's quicker to search during the translating phase.
            array_unshift($this->dictionaries, $dictionary);
            return true;
        }
    }


    /**
     * @param $name
     * @return Dictionary
     */
    public function getDictionary($name)
    {
        // If there is no dictionary by that name in the dictionaries
        // attached to the current file,
        if((0 == count($this->dictionaries)) || (! array_key_exists($name, $this->dictionaries)) ) {
            //if this file is root file (no parent), error!
            if(null == $this->parentFile) {
                return null;
            }
            //otherwise, search the parent file's dictionaries for the dictionary with specified name.
            return $this->parentFile->getDictionary($name);
        }
        //This will throw an exception if the entry is not found
        //in the current file's named dictionary, instead of searching parent's hierarchy for
        //dictionaries with the same name.
        return $this->dictionaries[$name];
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
}
