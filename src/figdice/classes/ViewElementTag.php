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

use figdice\exceptions\RequiredAttributeParsingException;
use figdice\exceptions\TagRenderingException;
use figdice\Filter;
use figdice\View;
use figdice\exceptions\RenderingException;

class ViewElementTag extends ViewElement implements \Serializable {


	/**
	 * Tag name.
	 * @var string
	 */
	private $name;

	protected $attributes;

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
     * The value for fig:cond attribute, or null if not present
     * @var string
     */
    private $figCond = null;
    /**
     * The value for fig:macro attribute, or null if not present
     * @var string
     */
	private $figMacro = null;
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
	 * @param string $name
	 * @param integer $xmlLineNumber
	 */
	public function __construct($name, $xmlLineNumber) {
		parent::__construct();
		$this->name = $name;
		$this->attributes = array();
		$this->children = array();
		$this->xmlLineNumber = $xmlLineNumber;
	}

	/**
	 * @return string
	 */
	public function getTagName() {
		return $this->name;
	}
	public function setAttributes($figNamespace, array $attributes) {
        if (array_key_exists($key = $figNamespace . 'auto', $attributes)) {
            $this->figAuto = $attributes[$key];
            unset($attributes[$key]);
        }
        if (array_key_exists($key = $figNamespace . 'call', $attributes)) {
            $this->figCall = $attributes[$key];
            unset($attributes[$key]);
        }
        if (array_key_exists($key = $figNamespace . 'cond', $attributes)) {
            $this->figCond = $attributes[$key];
            unset($attributes[$key]);
        }
        if (array_key_exists($key = $figNamespace . 'macro', $attributes)) {
            $this->figMacro = $attributes[$key];
            unset($attributes[$key]);
        }
        if (array_key_exists($key = $figNamespace . 'text', $attributes)) {
            $this->figText = $attributes[$key];
            unset($attributes[$key]);
        }
	    if (array_key_exists($key = $figNamespace . 'void', $attributes)) {
	        $this->figVoid = $attributes[$key];
	        unset($attributes[$key]);
        }
	    if (array_key_exists($key = $figNamespace . 'walk', $attributes)) {
	        $this->figWalk = $attributes[$key];
	        unset($attributes[$key]);
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

			if( ! $context->view->isFigAttribute($attribute)) {
        // a flag attribute is to be processed differently because it isn't a key=value pair.
				if ( !($value instanceof Flag) ) {
					if(preg_match_all('/\{([^\{]+)\}/', $value, $matches, PREG_OFFSET_CAPTURE)) {
						for($i = 0; $i < count($matches[0]); ++ $i) {
							$expression = $matches[1][$i][0];

							//Evaluate expression now:

							$evaluatedValue = $this->evaluate($context, $expression);
							if($evaluatedValue instanceof ViewElement) {
								$evaluatedValue = $evaluatedValue->render($context);
							}
							if(is_array($evaluatedValue)) {
								if(empty($evaluatedValue)) {
									$evaluatedValue = '';
								}
								else {
									$message = 'Attribute ' . $attribute . '="' . $value . '" in tag "' . $this->name . '" evaluated to array.';
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
	

							//Store evaluated value in $matches structure:
							$matches[0][$i][2] = $evaluatedValue;
						}

						//Now replace expressions right-to-left:
						for($i = count($matches[0]) - 1; $i >= 0; -- $i) {
							$evaluatedValue = $matches[0][$i][2];
							$outerExpressionPosition = $matches[0][$i][1];
							$outerExpressionLength = strlen($matches[0][$i][0]);
							$value = substr_replace($value, $evaluatedValue, $outerExpressionPosition, $outerExpressionLength);
						}
					}

				}

        if ($value instanceof Flag) {
          // Flag attribute: there is no value. We print only the name of the flag.
          $result .= " $attribute";
        }
        else {
          $result .= " $attribute=\"$value\"";
        }
			}

		}
		return $result;
	}
	function appendCDataChild($cdata)
	{
		if (trim($cdata) != '') {
			$this->autoclose = false;
		}
		$lastChild = null;

		//Position, if applies, a reference to element's a previous sibling.
		if( count($this->children) )
			$lastChild = & $this->children[count($this->children) - 1];

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
		$newElement->outputBuffer .= $cdata;
		$newElement->parent = & $this;
		$this->children[] = & $newElement;
	}

	function appendCDataSibling($cdata)
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
		// TODO: contradictory with the Wiki doc. Missing a unit test here to prove the point.
		if(null !== $this->figCond) {
			if((null === $this->figWalk) || $context->isBypassWalk()) {
				if(! $this->evalCondition($context)) {
					return '';
				}
			}
		}

		//================================================================
		//fig:case
		if(isset($this->attributes[$context->figNamespace . 'case'])) {
		    // Keep in mind that the case directive is written directly on the tag,
            // and there is no "switch" statement on its container.
            // So we must keep track at the parent level, of the current state of the case children.
			if($context->hasParent()) {
				if($context->isCaseSwitched()) {
					return '';
				}
				else {
					$condExpr = $this->attributes[$context->figNamespace . 'case'];
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
		//fig:trans
		if($this->name == $context->figNamespace . 'trans') {
			return $this->fig_trans($context);
		}

		//================================================================
		//fig:attr
		//Add to the parent tag the given attribute. Do not render.
		if( $this->name == $context->figNamespace . 'attr' ) {
			if(!isset($this->attributes['name'])) {
				//name is a required attribute for fig:attr.
				throw new RequiredAttributeParsingException($this->name, $this->xmlLineNumber, 'name');
			}
            //flag attribute
            // Usage: <tag><fig:attr name="ng-app" flag="true" />  will render as flag <tag ng-app> at tag level, without a value.
            if(isset($this->attributes['flag']) && $this->evaluate($context, $this->attributes['flag'])) {
			    $context->setParentRuntimeAttribute($this->attributes['name'], new Flag());
            }
            else {
                if ($this->hasAttribute('value')) {
                    $value = $this->evaluate($context, $this->attributes['value']);
                    if (is_string($value)) {
                        $value = htmlspecialchars($value);
                    }
                    $context->setParentRuntimeAttribute($this->attributes['name'],  $value);
                } else {
                    $value = '';
                    /**
                     * @var ViewElement
                     */
                    $child = null;
                    foreach ($this->children as $child) {
                        $renderChild = $child->render($context);
                        if ($renderChild === false) {
                            throw new \Exception();
                        }
                        $value .= $renderChild;
                    }
                    //An XML attribute should not span accross several lines.
                    $value = trim(preg_replace("#[\n\r\t]+#", ' ', $value));
                    $context->setParentRuntimeAttribute($this->attributes['name'], $value);
                }
            }
            return '';
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
		//Use short-circuit test if no fig:mute attribute, in order not
		//to evaluate needlessly.
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

            $context->setPreviousSibling(null);

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

                $context->setPreviousSibling($child);
			}
			$context->setPreviousSibling(null);
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
	private function evalAttribute(Context $context, $name) {
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
			if( ! $context->view->isFigAttribute($attribName) ) {
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
		if(isset($this->attributes[$context->figNamespace . 'filter'])) {
			$filterClass = $this->attributes[$context->figNamespace . 'filter'];
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
					if($previousSibling = $context->getPreviousSibling()) {
						if($previousSibling instanceof ViewElementCData) {
							if(($rtrim = rtrim($previousSibling->outputBuffer)) < $previousSibling->outputBuffer) {
								$blankRPart = substr($previousSibling->outputBuffer, strlen($rtrim));
								$precedingBlank = strrchr($blankRPart, "\n");
								if($precedingBlank === false) {
									$precedingBlank = $blankRPart;
								}
								$nextContent = $precedingBlank . $nextContent;
								$bFirstIteration = false;
							}
						}
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
     * Translates a caption given its key and dictionary name.
     * @param Context $context
     * @return string
     */
	private function fig_trans(Context $context) {

		//If a @source attribute is specified, and is equal to
		//the view's target language, then don't bother translating:
		//just render the contents.
	  $source = $this->getAttribute('source', null);
		
	  //The $key is also needed in logging below, even if
	  //source = view's language, in case of missing value,
	  //so this is a good time to read it.
	  $key = $this->getAttribute('key', null);
	  $dictionaryName = $this->getAttribute('dict', null);

		// Do we have a dictionary ?
		$dictionary = $context->getDictionary($dictionaryName);
		// Maybe not, but at this stage it is not important, I only need
		// to know its source
		$dicSource = ($dictionary ? $dictionary->getSource() : null);
		if (

			( (null == $source) &&	//no source on the trans tag
			 ($dicSource == $context->getView()->getLanguage()) )
			||
			($source == $context->getView()->getLanguage()) ) {
		    $context->pushDoNotRenderFigParams();
			$value = $this->renderChildren($context /*Do not render fig:param immediate children */);
			$context->popDoNotRenderFigParams();
		}

		else {
			//Cross-language dictionary mechanism:

			if(null == $key) {
				//Missing @key attribute : consider the text contents as the key.
				//throw new SyntaxErrorException($this->getCurrentFile()->getFilename(), $this->xmlLineNumber, $this->name, 'Missing @key attribute.');
				$key = $this->renderChildren($context);
			}
			//Ask current context to translate key:

            $value = $context->translate($key, $dictionaryName, $this->xmlLineNumber);
		}

		//Fetch the parameters specified as immediate children
		//of the macro call : <fig:param name="" value=""/>
		//TODO: Currently, the <fig:param> of a macro call cannot hold any fig:cond or fig:case conditions.
		$arguments = array();  
		foreach ($this->children as $child) {
			if($child instanceof ViewElementTag) {
				if($child->name == $this->view->figNamespace . 'param') {
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
		
		//We must now perform the replacements of the parameters of the translation,
		//which are written in the shape : {paramName}
		//and are specified as extra attributes of the fig:trans tag, or child fig:param tags
		//(fig:params override inline attributes).
		$matches = array();
		while(preg_match('/{([^}]+)}/', $value, $matches)) {
			$attributeName = $matches[1];
			//If there is a corresponding fig:param, use it:
			if(array_key_exists($attributeName, $arguments)) {
				$attributeValue = $arguments[$attributeName];
			}
			//Otherwise, use the inline attribute.
			else {
				$attributeValue = $this->evalAttribute($context, $attributeName);
			}
			$value = str_replace('{' . $attributeName . '}', $attributeValue, $value);
		}

		//If the translated value is empty (ie. we did find an entry in the proper dictionary file,
		//but this entry has an empty value), it means that the entry remains to be translated by the person in charge.
		//So in the meantime we output the key.
		if($value == '') {
			$value = $key;
			// TODO: One might want to be notified, either by an exception or another mechanism (Context logging?).
		}
		
		return $value;
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
		if($context->view->isFigAttribute($this->name)) {
			return true;
		}

		$expression = '';
		if(isset($this->attributes[$context->figNamespace . 'mute']))
			$expression = $this->attributes[$context->figNamespace . 'mute'];
		if($expression)
			return $this->evaluate($context, $expression);
		return false;
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


	// TODO: this is just a attempt at serializing the View object (and its tree of tags)
    // do not use in production.
	public function serialize()
    {
        $data = [
            'tag' => $this->name,
            'attr' => $this->attributes,
            'line' => $this->xmlLineNumber,
            'ac' => $this->autoclose,
            'tree' => $this->children,
        ];

        if ($this->figAuto) $data['auto'] = $this->figAuto;
        if ($this->figCall) $data['call'] = $this->figCall;
        if ($this->figCond) $data['cond'] = $this->figCond;
        if ($this->figMacro) $data['macro'] = $this->figMacro;
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

        $this->figAuto = isset($data['auto']) ? $data['auto'] : null;
        $this->figCall = isset($data['call']) ? $data['call'] : null;
        $this->figCond = isset($data['cond']) ? $data['cond'] : null;
        $this->figMacro = isset($data['macro']) ? $data['macro'] : null;
        $this->figText = isset($data['text']) ? $data['text'] : null;
        $this->figVoid = isset($data['void']) ? $data['void'] : null;
        $this->figWalk = isset($data['walk']) ? $data['walk'] : null;
    }
}
