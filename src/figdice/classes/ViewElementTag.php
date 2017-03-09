<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @package FigDice
 */

namespace figdice\classes;

use figdice\exceptions\RequiredAttributeException;
use figdice\exceptions\TagRenderingException;
use figdice\Filter;
use figdice\View;
use figdice\exceptions\RenderingException;

class ViewElementTag extends ViewElement implements \Serializable {


	/**
	 * Tag name.
	 * @var string
	 */
	protected $name;

	protected $attributes;

    protected $isDirective = false;

    /**
     * The value for fig:auto attribute, or null if not present
     * @var string
     */
    private $figAuto = null;
    /**
     * The value for fig:call attribute, or null if not present
     * @var string
     */
    private $figCall = null;
    /**
     * The value for fig:case attribute, or null if not present
     * @var string
     */
    private $figCase = null;
    /**
     * The value for fig:cond attribute, or null if not present
     * @var string
     */
    private $figCond = null;
    /**
     * The value for fig:filter attribute, or null if not present
     * @var string
     */
	private $figFilter = null;
    /**
     * The value for fig:macro attribute, or null if not present
     * @var string
     */
	private $figMacro = null;
    /**
     * The value for fig:mute attribute, or null if not present
     * @var string
     */
	private $figMute = null;
    /**
     * The value for fig:text attribute, or null if not present
     * @var string
     */
	private $figText = null;
    /**
     * The value for fig:void attribute, or null if not present
     * @var string
     */
	private $figVoid = null;
    /**
     * The value for fig:walk attribute, or null if not present
     * @var string
     */
	private $figWalk = null;



  /**
   * @var ViewElement[]
   */
	protected $children;

    /**
     * The LF & blank chars preceding the tag, if any.
     * @var null|string
     */
	private $blank;

	/**
	 * Indicates whether the XML tag must be rendered as html-void.
	 * This is useful in order to comply to the various (x)HTML doctypes.
	 * Example: br, hr, img, meta...
	 * <code>
	 *   <br fig:void="true" />
	 * </code>
	 * will ensure to render:
	 * <code>
	 *   <br>
	 * </code>
	 * with no closing tag, and no ending slash.
	 *
	 * @see http://www.456bereastreet.com/archive/201005/void_empty_elements_and_self-closing_start_tags_in_html/
	 * @var boolean
	 */
	private $voidtag;

	/**
	 * @var array of flags that indicate things to be bypassed during one specific rendering,
	 * such as macros, plugs, cases etc.
	 */
	private $transientFlags = [];

    /**
     *
     * @param string  $name
     * @param integer $xmlLineNumber
     * @param string|null    $previousBlank
     */
	public function __construct($name, $xmlLineNumber, $previousBlank = null) {
		parent::__construct();
		$this->name = $name;
		$this->attributes = array();
		$this->children = array();
		$this->xmlLineNumber = $xmlLineNumber;

		$this->blank = $previousBlank;
	}

	/**
	 * @return string
	 */
	public function getTagName() {
		return $this->name;
	}
	private function checkAndCropAttr($figNamespace, array & $attributes, $name)
    {
        $value = null;
        if (array_key_exists($key = $figNamespace . $name, $attributes)) {
            $value = $attributes[$key];
            $this->isDirective = true;
            unset($attributes[$key]);
        }
        return $value;
    }
	public function setAttributes($figNamespace, array $attributes) {
	    $this->figAuto = $this->checkAndCropAttr($figNamespace, $attributes, 'auto');
	    $this->figCall = $this->checkAndCropAttr($figNamespace, $attributes, 'call');
	    $this->figCase = $this->checkAndCropAttr($figNamespace, $attributes, 'case');
	    $this->figCond = $this->checkAndCropAttr($figNamespace, $attributes, 'cond');
	    $this->figFilter = $this->checkAndCropAttr($figNamespace, $attributes, 'filter');
	    $this->figMacro = $this->checkAndCropAttr($figNamespace, $attributes, 'macro');
	    $this->figMute = $this->checkAndCropAttr($figNamespace, $attributes, 'mute');
	    $this->figText = $this->checkAndCropAttr($figNamespace, $attributes, 'text');
	    $this->figVoid = $this->checkAndCropAttr($figNamespace, $attributes, 'void');
	    $this->figWalk = $this->checkAndCropAttr($figNamespace, $attributes, 'walk');

	    // Temporary: TODO: take care of slot and plug, to make me directive
        if (array_key_exists($figNamespace.'plug', $attributes)
            || array_key_exists($figNamespace.'slot', $attributes)
            || array_key_exists($figNamespace.'doctype', $attributes) ) {
            $this->isDirective = true;
        }

        if ($this->figWalk && ($this->blank !== null)) {
            if (preg_match('#(\n\s+)$#', $this->blank, $matches)) {
                $this->blank = $matches[1];
            }
            else {
                $this->blank = null;
            }
        }
        else {
            $this->blank = null;
        }

	    // Now take care of the remaining non-fig attributes
		$this->parseAttributes($figNamespace, $attributes);
	}

    /**
     * Split attributes by adhoc parts
     * and store the resulting array in the object member.
     *
     * @param string $figNamespace
     * @param array $attributes
     */
    protected function parseAttributes($figNamespace, array $attributes)
    {
        // A fig:call attribute on a tag, indicates that all the other attributes
        // are arguments for the macro. They all are considered as expressions,
        // and therefore there is no need to search for adhoc inside.
        if (! $this->figCall) {

            foreach ($attributes as $name => $value) {
                // Process the non-fig attributes only
                if (strpos($name, $figNamespace) === 0) {
                    continue;
                }

                // a flag attribute is to be processed differently because it isn't a key=value pair.
                // TODO: not sure we already have Flags at this stage of the cycle. I think
                // we only have plain real XML text attributes.
                if ( $value instanceof Flag) {
                    $this->isDirective = true;
                    continue;
                }

                // Search for adhocs
                if (preg_match_all('/\{([^\{]+)\}/', $value, $matches, PREG_OFFSET_CAPTURE)) {
                    $parts = [];
                    $previousPosition = 0;
                    for($i = 0; $i < count($matches[0]); ++ $i) {
                        $expression = $matches[1][$i][0];
                        $position = $matches[1][$i][1];
                        if ($position > $previousPosition + 1) {
                            // +1 because we exclude the leading {
                            $parts []= substr($value, $previousPosition, $position - 1 - $previousPosition);
                        }
                        $parts []= new AdHoc($expression);
                        // Mark the current tag as being an active cell in the template,
                        // as opposed to a stupid static string with no logic.
                        $this->isDirective = true;

                        // +1 because we contiunue past the trailing }
                        $previousPosition = $position + strlen($expression) + 1;
                    }
                    // And finish with the trailing static part, past the last }
                    if ($previousPosition < strlen($value)) {
                        $parts []= substr($value, $previousPosition);
                    }

                    // At this stage, $parts is an index array of pieces,
                    // each piece is either a scalar string, or an instance of AdHoc.
                    // If there is only one part, let's simplify the array
                    if (count($parts) == 1) {
                        $parts = $parts[0];
                    }

                    // $parts can safely replace the origin value in $attributes,
                    // because the serializing and rendering engine are aware.
                    $attributes[$name] = $parts;
                }

            }
        }

        $this->attributes = $attributes;
    }

	public function clearAttribute($name) {
		unset($this->attributes[$name]);
	}
	public function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
	}
	/**
	 * Determines whether the specified attribute
	 * is defined in the tag.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasAttribute($name) {
		return array_key_exists($name, $this->attributes);
	}


	public function appendChild(ViewElement & $child) {
		$this->children[] = $child;
	}

    /**
     * Returns a string containing the space-separated
     * list of XML attributes of an element.
     *
     * @param Context $context
     * @return string
     * @throws TagRenderingException
     */
	private function buildXMLAttributesString(Context $context) {
		$result = '';
		$matches = null;
		$attributes = $this->attributes;

		$runtimeAttributes = $context->getRuntimeAttributes();

		foreach($runtimeAttributes as $attributeName=>$runtimeAttr) {
			$attributes[$attributeName] = $runtimeAttr;
		}

		foreach($attributes as $attribute=>$value) {

			if( ! $context->view->isFigPrefix($attribute)) {
                // a flag attribute is to be processed differently because
                // it isn't a key=value pair.

                if ($value instanceof Flag) {
                    // Flag attribute: there is no value. We print only the name of the flag.
                    $result .= " $attribute";
                }


                else {

				    // We're potentially in presence of:
                    // - a plain scalar
                    // - an AdHoc instance
                    // - an array of the above.

                    if (! is_array($value)) {
                        $value = [$value];
                    }
                    $combined = '';
                    foreach ($value as $part) {
                        if ($part instanceof AdHoc) {
                            $evaluatedValue = $this->evaluate($context, $part->string);

                            if($evaluatedValue instanceof ViewElement) {
                                $evaluatedValue = $evaluatedValue->render($context);
                            }
                            if(is_array($evaluatedValue)) {
                                if(empty($evaluatedValue)) {
                                    $evaluatedValue = '';
                                }
                                else {
                                    $message = 'Adhoc {' . $part->string . '} of attribute ' . $attribute . ' in tag "' . $this->name . '" evaluated to array.';
                                    throw new TagRenderingException($this->getTagName(), $this->getLineNumber(), $message);
                                }
                            }
                            else if (is_object($evaluatedValue)
                                && ($evaluatedValue instanceof \DOMNode)) {

                                // Treat the special case of DOMNode descendants,
                                // for which we can evalute the text contents
                                $evaluatedValue = $evaluatedValue->nodeValue;
                            }

                            //The outcome of the evaluatedValue, coming from DB or other, might contain non-standard HTML characters.
                            //We assume that the FIG library targets HTML rendering.
                            //Therefore, let's have the outcome comply with HTML.
                            if(is_object($evaluatedValue)) {
                                //TODO: Log some warning!
                                $evaluatedValue = '### Object of class: ' . get_class($evaluatedValue) . ' ###';
                            }
                            else {
                                $evaluatedValue = htmlspecialchars($evaluatedValue);
                            }

                            $part = $evaluatedValue;
                        }

                        // Append to what we had already (and if $part was a string in the first place,
                        // we use its direct value;
                        $combined .= $part;


                    }
                    $value = $combined;

                    $result .= " $attribute=\"$value\"";
				}
            }

        }
		return $result;
	}
	public function appendCDataChild($cdata)
	{
		if (trim($cdata) != '') {
			$this->autoclose = false;
		}
		/** @var ViewElement $lastChild */
		$lastChild = null;

		//Position, if applies, a reference to element's a previous sibling.
		if( count($this->children) )
			$lastChild = $this->children[count($this->children) - 1];

		//If lastChild exists append a sibling to it.
		if($lastChild)
		{
			$lastChild->appendCDataSibling($cdata);
			return;
		}

		//Create a brand new node whose parent is the last node in stack.
		//Do not push this new node onto Depth Stack, beacuse CDATA
		//is necessarily autoclose.
		$newElement = new ViewElementCData();
		$newElement->outputBuffer = $cdata;
		$newElement->parent = & $this;
		$this->children[] = & $newElement;
	}

	public function appendCDataSibling($cdata)
	{
		//Create a brand new node whose parent is the last node in stack.
		//Do not push this new node onto Depth Stack, beacuse CDATA
		//is necessarily autoclose.
		$newElement = new ViewElementCData();
		$newElement->outputBuffer .= $cdata;
		$newElement->parent = & $this->parent;
		$this->parent->children[] = & $newElement;
	}

    /**
     * Computes the fig:cond condition that the tag holds,
     * if it is found in the attributes,
     * and returns its result.
     * Returns true if there was no condition attached to the tag.
     *
     * @param Context $context
     * @return bool
     */
	private function evalCondition(Context $context) {
		return (null !== $this->figCond) ? $this->evaluate($context, $this->figCond) : true;
	}


	/**
	 * Render a node tree (recursively).
	 *
     * @param Context $context
	 * @return string
	 */
	public function render(Context $context) {
	    $context->pushTag($this);
		//================================================================
		//fig:macro
		//There is no condition to a macro definition.
		//A tag bearing the fig:macro directive is not implied mute.
		//It can render as a regular outer tag. If needed, it must be explicitly muted.
		if(null !== $this->figMacro) {
		    $result = $this->fig_macro($context);
		}
		else {
            $result = $this->renderNoMacro($context);

            // Clear transient flags
            $this->transientFlags = [];
        }
        $context->popTag();
		return $result;
	}
	private function renderNoMacro(Context $context) {


		//================================================================
		//fig:cond
		//If the tag holds a fig:walk directive as well as the fig:cond,
		//do not take into account this fig:cond unless the rendering is inside the looping phase,
		//because the condition pertains to every single iteration, rather than to the global loop.
        // See WalkTest::testCondOnWalkAppliesToEachIter() for proof.
		if(null !== $this->figCond) {
			if((null === $this->figWalk) || $context->isBypassWalk()) {
				if(! $this->evalCondition($context)) {
					return '';
				}
			}
		}

		//================================================================
		//fig:case
		if($this->figCase) {
		    // Keep in mind that the case directive is written directly on the tag,
            // and there is no "switch" statement on its container.
            // So we must keep track at the parent level, of the current state of the case children.
			if($context->hasParent()) {
				if($context->isCaseSwitched()) {
					return '';
				}
				else {
					$condExpr = $this->figCase;
					$condVal = $this->evaluate($context, $condExpr);
					if(! $condVal) {
						return '';
					}
					else {
						$context->setCaseSwitched();
					}
				}
			}
		}


		//================================================================
        // Here, we can let a special Fig Tag perform its specific treatment.
        // Example: trans.
        // Doing this here, lets the tag benefit from previous logic (fig directive attributes)
        // such as cond, case etc.
        if ($context->view->isFigPrefix($this->name)) {
		    $specificResult = $this->doSpecific($context);
		    if (null !== $specificResult) {
		        return $specificResult;
            }
        }




		//================================================================
		//fig:walk
		//Loop over evaluated dataset.
		if(! $context->isBypassWalk()) {
			if(null !== $this->figWalk) {
				return $this->fig_walk($context);
			}
		}

		//================================================================
		//fig:call
		//A tag with the fig:call directive is necessarily mute.
		//It is used as a placeholder only for the directive.
		if(null !== $this->figCall) {
			return $this->fig_call($context);
		}




		//================================================================
		//fig:slot
		//Define the named slot at View level, pointing on the
		//current position of the rendering output.
		//Then, render the slot element, as a default content in case
		//nothing is plugged into it.
		//In case of plug for this slot, the complete slot tag (outer) is replaced.
		if($slotName = $this->getFigAttribute($context->figNamespace, 'slot')) {

            //Store a reference to current node, into the View's map of slots

			$anchorString = '/==SLOT==' . $slotName . '==/';
			$slot = new Slot($anchorString);
			$context->assignSlot($slotName, $slot);

			unset($this->attributes[$context->figNamespace . 'slot']);
			$result = $this->render($context);
			if($result === false)
				throw new \Exception();
			$slot->setLength(strlen($result));
			$this->attributes[$context->figNamespace . 'slot'] = $slotName;
			return $anchorString . $result;
		}

		//================================================================
		//fig:plug
		//An element bearing the fig:plug attribute
		//is to be rendered in place of the other element bearing
		//the corresponding fig:slot attribute.
		//The complete plug tag (outer) is rendered in place of the slot.
		//At slots-filling time, if a plug has a fig:append attribute evaluating to true,
		//its content is appended to whatever was already filled into the slot.
		//The entire plug+append tag (outer) is appended to the slot placeholder
		//(the slot's outer tag being removed).
		if(($slotName = $this->getFigAttribute($context->figNamespace, 'plug')) && ! $this->isTransient(self::TRANSIENT_PLUG_RENDERING)) {

			//Keep track of the callback node (the fig:plug node).
			//The callbacks are maintained as a chain. Several callbacks
			//can enchain one after the other,
			//in the order they were parsed.
			if ($context->view->hasOption(View::GLOBAL_PLUGS)) {
				//The plugs are rendered at the end of the rendering of the View.
				$context->addPlug($slotName);
			}
			else {
				// The plugs are rendered in their local context
				// (but still, remain stuffed at the end of the template rendering)
				$this->transient(self::TRANSIENT_PLUG_RENDERING);
				$context->addPlug($slotName, $this->render($context), $this->evalFigAttribute($context, 'append'));
			}

			//A fig:plug tag does not produce any in-place output.
			return '';
		}



		//================================================================
		//fig:auto
		//
		//This attribute means that the tag should render auto-closed, even though it
		//may be exploded for the purpose of assigning fig:attr children.
		if(null !== $this->figAuto) {
			$expression = $this->figAuto;
			if($this->evaluate($context, $expression)) {
			    // The empty string here is important, rather than null,
                // because it is needed by the part that renders the children of a fig:auto tag.
				$this->figText = '';
				$this->autoclose = true;
			}
		}

		//================================================================
		//fig:void
		//
		//This attribute means that the tag should render as HTML-void (such as: <br>)
		//and its contents discarded.
		if(null !== $this->figVoid) {
			$expression = $this->figVoid;
			if($this->evaluate($context, $expression)) {
				$this->figText = null;
				$this->voidtag = true;
			}
		}

		//================================================================
		//fig:text
		//
		//Instead of rendering current tag, replace its contents
		//with expression.
		//If expression is a symbol that represents an argument to
		//a macro call, of class ViewElementTag, then do not lex-eval
		//the expression but rather render this tag. This allows passing to a macro call
		//a complex fig:param (a piece of xml).
		//
		//Only the immediate Tag children of name fig:attr are parsed,
		//so that it is still possible to have dynamic attributes to a tag bearing fig:text.
		if(null !== $this->figText) {
            $content = $this->figText;

            $output = $this->evaluate($context, $content);
            if($output === null) {
                $this->outputBuffer = '';
            }
            else {

                // Unfortunately there is no auto __toString
                // for DOMNode objects (not even DOMText...)
                if (is_object($output)
                    && ($output instanceof \DOMNode)) {
                    $output = $output->nodeValue;
                }

                if (is_object($output)) {
                    // Try to convert object to string, using PHP's cast mechanism (possibly involving __toString() method).
                    try {
                        $output = (string) $output;
                    } catch(\ErrorException $ex) {
                        throw new RenderingException($this->getTagName(),
                            $context->getFilename(),
                            $this->getLineNumber(),
                            $ex->getMessage()
                        );
                    }
                }

                // Since we're in a fig:text directive, make sure we'll output a string (even if we got a bool)
                $output = (string) $output;
                $this->outputBuffer = $output;
				
				
				
                if (trim($output) != '') {
                    //We clear the autoclose flag only if there is any meaningful
                    //inner content.
                    $this->autoclose = false;
                }
            }

            if(! $this->isMute($context)) {
                //Take care of inner fig:attr
                for($iChild = 0; $iChild < count($this->children); ++$iChild) {
                    $child = & $this->children[$iChild];
                    if($child instanceof TagFigAttr) {
                        $child->render($context);
                    }
                }
            }
        }


		//Now proceed with the children...
		$result = $this->renderChildren($context);



		//Let's apply the potential filter on the inner parts.
		$result = $this->applyOutputFilter($context, $result);

		//And then, render the outer XML part of the tag, if not mute.

		//fig:mute
		//If this attribute is set and true,
		//then the current element is rendered withouts its
		//outer tag.
		//Every fig: tag is mute by nature.
		if(! $this->isMute($context)) {
			$xmlAttributesString = $this->buildXMLAttributesString($context);

			if ($this->voidtag) {
				$result = '<' . $this->name . $xmlAttributesString . '>';
			}
			else {
				if($result instanceof ViewElementTag) {
					$innerResults = $result->render($context);
				}
				else {
					$innerResults = $result;
				}
				
				// If the inner content is the result of some XML XPath
				// leading to a DOMText value, we can safely use the underlying
				// text for the object. Unfortunately there is no 
				// native __toString() there :(
				if (is_object($innerResults) 
				    && ($innerResults instanceof \DOMNode)) {
				  $innerResults = $innerResults->nodeValue;
				}
				
				$result =
					'<' . $this->name . $xmlAttributesString .
					($this->autoclose ? ' /' : '') .
				 '>' . $innerResults;
				if(! $this->autoclose) {
					$result .= '</' . $this->name . '>';
				}
			}


			//Clear runtime attributes (fig:attr) now that we have used them,
			//for potential next iteration of same tag,
			//because runtime attributes could be conditioned and the condition could eval differently
			//in next iteration.
            $context->clearRuntimeAttributes();
		}


		return $result;
	}

	protected function renderChildren(Context $context) {

        $doNotRenderFigParam = $context->isDoNotRenderFigParams();

		$result = '';
		//If a fig treatment happened already, then outputBuffer contains
		//the result to use. Otherwise, it needs to be calculated recursively
		//with the children.
		if($this->outputBuffer === null) {
			//A mute tag that does not have any content rendered yet, should not output the consecutive blank cdata.
			if( $this->isMute($context) ) {
				if(isset($this->children[0]) && ($this->children[0] instanceof ViewElementCData) ) {
					//TODO: comprendre pourquoi il me reste des blancs avant ?xml
					$this->children[0]->outputBuffer = preg_replace('#^[ \\t]*\\n#', '', $this->children[0]->outputBuffer);
				}
			}

			for($iChild = 0; $iChild < count($this->children); ++$iChild) {
				$child = & $this->children[$iChild];
				if($doNotRenderFigParam && ($child instanceof ViewElementTag) && ($child->getTagName() == $context->figNamespace . 'param') ) {
					//This situation is encountered when a sourced fig:trans tag has a fig:param immediate child:
					//the fig:param is used to resolve the translation, but it must not be rendered in the value of the
					//fig:trans tag (because it is sourced, it means that the translation is not to be taken from an external dictionary,
					//but rather directly the rendered content of the fig:trans tag).
					continue;
				}

				$subRender = $child->render($context);
				//Caution: it used to work fine, and then I got a: Could not
				//convert ViewElementTag to string,
				//as I called a macro with a fig:param being some XML piece, instead of a plain value.
				//So I added this additional step: the rendering of the said ViewElementTag.
				if($subRender instanceof ViewElement) {
					$subRender = $subRender->render($context);
				}
				$result .= $subRender;

			}
		}
		else {
			$result = $this->outputBuffer;
		}

		return $result;
	}

    /**
     * Evaluates the expression written in specified attribute.
     * If attribute does not exist, returns false.
     * @param Context $context
     * @param string $name Attribute name
     * @return mixed
     */
	protected function evalAttribute(Context $context, $name) {
		$expression = $this->getAttribute($name, false);
		if($expression) {
			return $this->evaluate($context, $expression);
		}
		return false;
	}

    /**
     * @param Context $context
     * @param string $name the fig attribute to evaluate
     * @return mixed or false if attribute not found.
     */
	public function evalFigAttribute(Context $context, $name)
	{
		return $this->evalAttribute($context, $context->figNamespace . $name);
	}

	public function getAttribute($name, $default = null) {
		if(isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		return $default;
	}
	private function getFigAttribute($figNamespace, $name, $default = null) {
		return $this->getAttribute($figNamespace . $name, $default);
	}


    /**
     * Defines a macro in the dictionary of the
     * topmost view.
     * @param Context $context
     * @return string
     */
	private function fig_macro(Context $context) {
		$context->view->macros[$this->figMacro] = & $this;
		return '';
	}

    /**
     * Renders the call to a macro.
     * No need to hollow the tag that bears the fig:call attribute,
     * because the output of the macro call replaces completely the
     * whole caller tag.
     * @param Context $context
     * @return string
     */
	private function fig_call(Context $context) {
		//Retrieve the name of the macro to call.
		$macroName = $this->figCall;

		//Prepare the arguments to pass to the macro:
		//all the non-fig: attributes, evaluated.
		$arguments = array();
		foreach($this->attributes as $attribName => $attribValue) {
			if( ! $context->view->isFigPrefix($attribName) ) {
				$value = $this->evaluate($context, $attribValue);
				$arguments[$attribName] = $value;
			}
		}


		//Fetch the parameters specified as immediate children
		//of the macro call : <fig:param name="" value=""/>
		$arguments = array_merge($arguments, $this->collectParamChildren($context));

		//Retrieve the macro contents.
		if(isset($context->view->macros[$macroName])) {
		    /** @var ViewElementTag $macroElement */
			$macroElement = & $context->view->macros[$macroName];
			$context->view->pushStackData($arguments);

			// Hide any current iteration during macro invocation
            $context->pushIteration(new Iteration(0));

			//Now render the macro contents, but do not take into account the fig:macro
			//that its root tag holds.
			$result = $macroElement->renderNoMacro($context);

			// Restore the previous iteration context
            $context->popIteration();

			$context->view->popStackData();
			return $result;
			//unset($macroElement->iteration);
		}
		return '';
	}

    /**
     * Returns an array of named values obtained by the immediate :param children of the tag.
     * The array is to be merged with the other arguments provided by inline
     * attributes of the :call tag.
     * @param Context $context
     * @return array
     */
	private function collectParamChildren(Context $context) {
		$arguments = array();

		//Fetch the parameters specified as immediate children
		//of the macro call : <fig:param name="" value=""/>
		//TODO: <fig:param> can hold fig:cond ok, but we must implement no fig:case
		foreach ($this->children as $child) {
			if($child instanceof ViewElementTag) {
				if($child->name == $context->figNamespace . 'param') {

					//evalute the fig:cond on the param
					if(! $child->evalCondition($context)) {
						continue;
					}
					//If param is specified with an immediate value="" attribute :
					if(isset($child->attributes['value'])) {
						$arguments[$child->attributes['name']] = $this->evaluate($context, $child->attributes['value']);
					}
					//otherwise, the actual value is not scalar but is
					//a nodeset in itself. Let's pre-render it and use it as text for the argument.
					else {
						$arguments[$child->attributes['name']] = $child->render($context);
					}
				}
			}
		}
		return $arguments;
	}

    /**
     * Applies a filter to the inner contents of an element.
     * Returns the filtered output.
     *
     * @param Context $context
     * @param string $buffer the inner contents of the element, after rendering.
     * @return string
     */
	private function applyOutputFilter(Context $context, $buffer) {
		//TODO: Currently the filtering works only on non-slot tags.
		//If applied on a slot tag, the transform is made on the special placeholder /==SLOT=.../
		//rather than the future contents of the slot.
		if($this->figFilter) {
			$filterClass = $this->figFilter;
			$filter = $this->instantiateFilter($context, $filterClass);
			$buffer = $filter->transform($buffer);
		}
		return $buffer;
	}

    /**
     * Iterates the rendering of the current element,
     * over the data specified in the fig:walk attribute.
     *
     * @param Context $context
     * @return string
     */
	private function fig_walk(Context $context) {
		$figIterateAttribute = $this->figWalk;
		$dataset = $this->evaluate($context, $figIterateAttribute);

		//Walking on nothing gives no ouptut.
		if(null == $dataset) {
			return '';
		}

		$outputBuffer = '';

		if(is_object($dataset) && ($dataset instanceof \Countable) ) {
			$datasetCount = $dataset->count();
		}
		else if(is_array($dataset)) {
			$datasetCount = count($dataset);
		}
		else {
			//When requested to walk on a scalar or a single object,
			//do as if walking on an array containing this single element.
			$dataset = array($dataset);
			$datasetCount = 1;
		}

		$newIteration = new Iteration($datasetCount);
		$context->pushIteration($newIteration);
		$bFirstIteration = true;

		if(is_array($dataset) || (is_object($dataset) && ($dataset instanceof \Countable)) ) {

		    // Indicate that the current tag is entering walk loop,
            // so as subsequent rendering of self will not cause an infinite loop.
            $context->setBypassWalk();
			foreach($dataset as $key => $data) {
				$context->view->pushStackData($data);
				$newIteration->iterate($key);
				// It is necessary to invoke again 'render' on self,
                // because the walk directive is only here to create a loop,
                // but the tag must actually be rendered along with all its eval expressions with inner data.
                $nextContent = $this->renderNoMacro($context);


                //Each rendered iteration start again
				//with the blank part on the right of the preceding CDATA,
				//so that the proper indenting is kept, and carriage returns
				//between each iteration, if applies.
				if(! $bFirstIteration) {
					if($this->blank !== null) {
                        $nextContent = $this->blank . $nextContent;
                        $bFirstIteration = false;
					}
				}
				//But only do this for subsequent iterations, not the first one,
				//since its preceding sibling was already rendered beforehand.
				else {
					$bFirstIteration = false;
				}

				$outputBuffer .= $nextContent;
				$context->view->popStackData();
			}
		}
		$context->popIteration();
		return $outputBuffer;
	}




    /**
     * Determines whether the current Tag bears the mute directive,
     * in which case we render only its inner parts,
     * without an outer tag.
     * A <fig: > tag is always mute.
     *
     * @param Context $context
     * @return bool
     */
	private function isMute(Context $context) {
	    // <fig:...> tag?
		if($context->view->isFigPrefix($this->name)) {
			return true;
		}

        return ($this->figMute) ? $this->evaluate($context, $this->figMute) : false;
	}

    /**
     * @param Context $context
     * @param string $className
     * @return Filter
     * @throws RenderingException
     */
	private function instantiateFilter(Context $context, $className) {
		if($context->view->getFilterFactory())
			return $context->view->getFilterFactory()->create($className);

		$reflection = new \ReflectionClass($className);
		$instance = $reflection->newInstance();
		if (! $instance instanceof Filter) {
		    throw new RenderingException($this->getTagName(),
                $context->getFilename(),
                $this->getLineNumber(),
                'Class ' . get_class($instance) . ' is not a Filter.');
        }
        return $instance;
	}



	/**
	 * Checks whether the current object has a specified rendering flag.
	 * Transient flags are automatically cleared at the end of the tag's rendering loop.
	 * @param $flag
	 * @return bool
	 */
	private function isTransient($flag)
	{
		return isset($this->transientFlags[$flag]);
	}
	private function transient($flag)
	{
		$this->transientFlags[$flag] = true;
	}

	/**
	 * This const is used internally to indicate that the object is currently being rendered as a local plug.
     * @internal
	 */
	const TRANSIENT_PLUG_RENDERING = 'TRANSIENT_PLUG_RENDERING';


	public function serialize()
    {
        $data = [
            'tag' => $this->name,
            'attr' => $this->attributes,
            'line' => $this->xmlLineNumber,
            'ac' => $this->autoclose,
            'tree' => $this->children,
        ];

        if ($this->blank !== null) {
            $data['blank'] = $this->blank;
        }

        if ($this->figAuto) $data['auto'] = $this->figAuto;
        if ($this->figCall) $data['call'] = $this->figCall;
        if ($this->figCase) $data['case'] = $this->figCase;
        if ($this->figCond) $data['cond'] = $this->figCond;
        if ($this->figFilter) $data['filter'] = $this->figFilter;
        if ($this->figMacro) $data['macro'] = $this->figMacro;
        if ($this->figMute) $data['mute'] = $this->figMute;
        if ($this->figText) $data['text'] = $this->figText;
        if ($this->figVoid) $data['void'] = $this->figVoid;
        if ($this->figWalk) $data['walk'] = $this->figWalk;

        return serialize($data);
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->name = $data['tag'];
        $this->attributes = $data['attr'];
        $this->xmlLineNumber = $data['line'];
        $this->autoclose = $data['ac'];
        $this->children = $data['tree'];
        $this->blank = isset($data['blank']) ? $data['blank'] : null;

        $this->figAuto = isset($data['auto']) ? $data['auto'] : null;
        $this->figCall = isset($data['call']) ? $data['call'] : null;
        $this->figCase = isset($data['case']) ? $data['case'] : null;
        $this->figCond = isset($data['cond']) ? $data['cond'] : null;
        $this->figFilter = isset($data['filter']) ? $data['filter'] : null;
        $this->figMacro = isset($data['macro']) ? $data['macro'] : null;
        $this->figMute = isset($data['mute']) ? $data['mute'] : null;
        $this->figText = isset($data['text']) ? $data['text'] : null;
        $this->figVoid = isset($data['void']) ? $data['void'] : null;
        $this->figWalk = isset($data['walk']) ? $data['walk'] : null;
    }

    /**
     * Give room for specific tags to perform their overridden operations.
     * The null return value indicates that it did not produce final output: the
     * engine must continue normally.
     * To the contrary, a non-null (strict) result shortcuts the rendering of the tag,
     * and the result bubbles up the tree.
     * @param Context $context
     * @return mixed|null
     */
    protected function doSpecific(Context $context)
    {
        return null;
    }

    /**
     * Indicates whether the tag contains a fig: attribute anyhow,
     * or has anny adhoc parts in its plain attributes.
     * If not, it is a good candidate for checking if it is on the whole
     * a static string that the rendering could dump unprocessed.
     * @return bool
     */
    public function isDirective()
    {
        return $this->isDirective;
    }

    /**
     * This method attempts to simplify the current element.
     * If optimizations apply, as far as squashing everything down to a single CData item, returns this new cdata item.
     * If it leads to a simplified tree, with the outer envelope made static, an new array of children is returned.
     * If no optimizations were possible, returns null.
     *
     * @param bool $noEnvelope
     *
     * @return ViewElementContainer|ViewElementCData|null
     */
    public function makeSquashedElement($noEnvelope) {
        // First, check I am holding a fig attribute
        // of any adhoc part in a plain attribute
        if ($this->isDirective())
            return null;

        // Now, check if I contain only CData children,
        // all the while preparing my envelope string

        if ($noEnvelope) {
            $envelope = '';
        }
        else {
            $envelope = '<' . $this->getTagName();
            if ( count($this->attributes) ) {
                $attrWithValues = [];
                foreach ( $this->attributes as $attrName => $attrValue ) {
                    $attrWithValues [] = $attrName . '="' . $attrValue . '"';
                }

                $attrString = implode(' ', $attrWithValues);
                $envelope .= ' ' . $attrString;
            }

            if ( $this->autoclose ) {
                $envelope .= ' />';

                return new ViewElementCData($envelope, $this->parent);
            }

            $envelope .= '>';
        }

        // The array of immediate children cannot contain two consecutive CData items
        // If there are :attr items, they must be the first active child (ie 0 or 1 if 0 is cdata)

        if (count($this->children) > 0) {
            if ($this->children[0] instanceof TagFigAttr) {
                // The first useful child is fig:attr  : no simplifications can be done.
                return null;
            }
            if ( (count($this->children) > 1)
                 && ($this->children[0] instanceof ViewElementCData)
                 && ($this->children[1] instanceof TagFigAttr) ) {
                // First child is cdata, but immediately followed by a fig:attr
                return null;
            }
        }

        // At this point, we may have tags in the children array, but they won't
        // change my outer envelope.
        // I can substitute myself with a starting cdata (containing my opening tag and my leading cdata)
        // then the tree (except the first and/or last child if they're cdata)
        // then the trailing cdata and the closing tag.

        $ending = '';

        $nbChildren = count($this->children);
        $newChildren = [];
        $iChild = 0;
        foreach ($this->children as $child) {
            if ( ($iChild == 0) && ($child instanceof ViewElementCData) ) {
                if ($noEnvelope) {
                    // For a mute tag, if the first child is plain string,
                    // we will discard the part from the end of the opening tag, till the linefeed (included).
                    $envelope .= preg_replace('#^[ \\t]*\\n#', '', $child->outputBuffer);
                }
                else {
                    $envelope .= $child->outputBuffer;
                }
            }
            else if ( ($iChild == $nbChildren - 1) && ($child instanceof ViewElementCData) ) {
                $ending = $child->outputBuffer;
            }
            else {
                // In case one of the children has a fig:case directive,
                // we're compelled to cancel the squashing,
                // because the parent tag (even inert!) of fig:case children must be isolated at render time
                // and becomes active.
                if ( ($child instanceof ViewElementTag) && ($child->figCase !== null) ) {
                    return null;
                }
                $newChildren []= $child;
            }
            ++ $iChild;
        }

        if (! $noEnvelope) {
            $ending .= '</' . $this->getTagName() . '>';
        }

        if ( (count($newChildren) == 0) && ($envelope . $ending)) {
            return new ViewElementCData($envelope . $ending, $this->parent);
        }

        if ($envelope) {
            array_unshift($newChildren, new ViewElementCData($envelope, $this->parent));
        }
        if ($ending) {
            $newChildren [] = new ViewElementCData($ending, $this->parent);
        }
        if (count($newChildren)) {
            return new ViewElementContainer($newChildren, $this->parent);
        }
        return null;

    }


    /**
     * If the last but one child is already a CData,
     * squash them together.
     */
    private function replaceLastChild_cdata(ViewElementCData $cdata)
    {
        $n = count($this->children);
        if ( ($n > 1) && ($this->children[$n - 2] instanceof ViewElementCData) ) {
            // Group the last-but-one cdata with this new cdata
            $this->children[$n - 2]->outputBuffer .= $cdata->outputBuffer;
            // and chop the final element off the children array.
            array_pop($this->children);
        }
        else {
            $cdata->parent        = $this;
            $this->children[$n - 1] = $cdata;
        }
    }

    private function replaceLastChild_container(ViewElementContainer $container) {
        $container->parent = $this;

        // If last-but-one child is cdata, and container starts with cdata,
        // combine the cdata into the first child of container and suppress the
        // last-but-one cdata child of this.
        $n = count($this->children);
        if ($n > 1) {

            if ( ($this->children[$n - 2] instanceof ViewElementCData)
                 && ($container->children[0] instanceof ViewElementCData) ) {

                $container->children[0]->outputBuffer =
                    $this->children[ $n - 2 ]->outputBuffer . $container->children[0]->outputBuffer;
                array_splice($this->children, $n - 2, 1);
            }
            else if ($this->children[$n - 2] instanceof ViewElementContainer) {
                // We will merge 2 containers
                $this->children[$n - 2]->children = array_merge($this->children[$n - 2]->children, $container->children);
                array_splice($this->children, $n - 1, 1);
                return;
            }
        }

        $this->children[count($this->children) - 1] = $container;
    }

    public function replaceLastChild(ViewElement $element) {
        if ($element instanceof ViewElementCData) {
            $this->replaceLastChild_cdata($element);
        }
        else if($element instanceof ViewElementContainer) {
            $this->replaceLastChild_container($element);
        }
    }
}
