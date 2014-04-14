<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.4
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

use Psr\Log\LoggerIntergace;
use figdice\View;
use figdice\LoggerFactory;
use figdice\exceptions\RenderingException;
use figdice\exceptions\DictionaryDuplicateKeyException;
use figdice\exceptions\RequiredAttributeException;
use figdice\exceptions\FeedClassNotFoundException;
use figdice\exceptions\FileNotFoundException;

class ViewElementTag extends ViewElement {
	/**
	 * Tag name.
	 * @var string
	 */
	private $name;

	private $attributes;
	private $runtimeAttributes;

	private $children;

	/**
	 * If an immediate child has a fig:case attribute,
	 * if this caseSwitched boolean attribute is set, it will be skipped.
	 * If caseSwitched is not set,
	 * the fig:case acts as a fig:cond, and in case of success,
	 * this caseSwitched boolean is set.
	 *
	 * @var boolean
	 */
	private $caseSwitched;

	/**
	 * The actual file that contains the current tag. Useful for nested includes,
	 * to determine the real path of the relatively specified filename to include.
	 * Also useful for translation, because of the stacked model of dictionaries :
	 * a dictionary is attached to a fig file, so if the fig file was included in a
	 * parent one, after "returning" from the include, the parent fig file no longer sees
	 * the dictionaries declared in the child fig.
	 * @var File
	 */
	private $currentFile;


	/**
	 * Flag indicating whether the tag must be rendered as HTML-void
	 * (example: <img>) which means: without a closing tag, and without
	 * an ending / (unlike auto-close flag).
	 * This behaviour is controlled by the fig:void attribute.
	 */
	private $voidtag;

	/**
	 * 
	 * @param View $view
	 * @param string $name
	 * @param integer $xmlLineNumber
	 */
	public function __construct(View &$view, $name, $xmlLineNumber) {
		parent::__construct($view);
		$this->name = $name;
		$this->attributes = array();
		$this->runtimeAttributes = array();
		$this->children = array();
		$this->xmlLineNumber = $xmlLineNumber;
	}

	/**
	 * We accept here a null argument, so as to make it possible
	 * to source a View by direct String rather that loading a physical file.
	 * @todo This is not the best approach. It will be preferable to
	 * provide a native View::loadFromString mechanism, which will handle
	 * properly the null-file special case.
	 *
	 * @param File & $file
	 */
	public function setCurrentFile(File & $file = null) {
		$this->currentFile = & $file;
	}
	/**
	 * @return File
	 */
	public function getCurrentFile() {
		return $this->currentFile;
	}
	/**
	 * @return string
	 */
	protected function getCurrentFilename() {
		if ( (null != $this->currentFile) && ($this->currentFile instanceof File) ) {
			return $this->currentFile->getFilename();
		}
		return '(null)';
	}
	/**
	 * @return string
	 */
	public function getTagName() {
		return $this->name;
	}
	public function setAttributes(array $attributes) {
		$this->attributes = $attributes;
	}
	public function getAttributes() {
		return $this->attributes;
	}
	/**
	 * @return integer
	 */
	public function getChildrenCount() {
		return count($this->children);
	}
	/**
	 * @param integer $index
	 * @return ViewElement
	 */
	public function getChildNode($index) {
		return $this->children[$index];
	}
	/**
	 * @return ViewElement
	 */
	public function & lastChild() {
		if(count($this->children)) {
			return $this->children[count($this->children) - 1];
		}
		return null;
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
		if(0 < count($this->children)) {
			$this->children[count($this->children) - 1]->nextSibling = & $child;
			$child->previousSibling = & $this->children[count($this->children) - 1];
		}
		$this->children[] = $child;
	}
	/**
	 * Returns a string containing the space-separated
	 * list of XML attributes of an element.
	 *
	 * @return string
	 */
	function buildXMLAttributesString() {
		$result = '';
		$matches = null;
		$attributes = $this->attributes;

		foreach($this->runtimeAttributes as $attributeName=>$runtimeAttr) {
			$attributes[$attributeName] = $runtimeAttr;
		}

		foreach($attributes as $attribute=>$value) {

			if( ! $this->view->isFigAttribute($attribute)) {
				if($value instanceof ViewElementTag) {
					$value = $value->render();
				}
				else {
					if(preg_match_all('/\{([^\{]+)\}/', $value, $matches, PREG_OFFSET_CAPTURE)) {
						for($i = 0; $i < count($matches[0]); ++ $i) {
							$expression = $matches[1][$i][0];
							$outerExpressionPosition = $matches[0][$i][1];
							$outerExpressionLength = strlen($matches[0][$i][0]);

							//Evaluate expression now:

							$evaluatedValue = $this->evaluate($expression);
							if($evaluatedValue instanceof ViewElement) {
								$evaluatedValue = $evaluatedValue->render();
							}
							if(is_array($evaluatedValue)) {
								if(empty($evaluatedValue)) {
									$evaluatedValue = '';
								}
								else {
									$message = 'Attribute ' . $attribute . '="' . $value . '" in tag "' . $this->name . '" evaluated to array.';
									$message = get_class($this) . ': file: ' . $this->currentFile->getFilename() . '(' . $this->xmlLineNumber . '): ' . $message;
									if(! $this->logger) {
										$this->logger = LoggerFactory::getLogger(get_class($this));
									}
									$this->logger->error($message);
									throw new Exception($message);
								}
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
	
							if($this->view->error) {
								return '';
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

				$result .= " $attribute=\"$value\"";
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
		$newElement = new ViewElementCData($this->view);
		$newElement->outputBuffer .= $cdata;
		$newElement->parent = & $this;
		$newElement->previousSibling = null;
		if(count($this->children))
		{
			$newElement->previousSibling = & $this->children[count($this->children) - 1];
			$newElement->previousSibling->nextSibling = & $newElement;
		}
		$this->children[] = & $newElement;
	}

	function appendCDataSibling($cdata)
	{
		//Create a brand new node whose parent is the last node in stack.
		//Do not push this new node onto Depth Stack, beacuse CDATA
		//is necessarily autoclose.
		$newElement = new ViewElementCData($this->view);
		$newElement->outputBuffer .= $cdata;
		$newElement->parent = & $this->parent;
		$newElement->previousSibling = & $this;
		$this->nextSibling = & $newElement;
		$this->parent->children[] = & $newElement;
	}

	/**
	 * Computes the fig:cond condition that the tag holds,
	 * if it is found in the attributes,
	 * and returns its result.
	 * Returns true if there was no condition attached to the tag.
	 *
	 * @return boolean
	 */
	private function evalCondition() {
		$condExpr = $this->getAttribute($this->view->figNamespace . 'cond');
		if($condExpr) {
			return $this->evaluate($condExpr);
		}
		return true;
	}


	/**
	 * Render a node tree (recursively).
	 *
	 * @param boolean $bypassWalk used by the self-rendering walk tag when calling render on itself.
	 * @return string
	 */
	public function render($bypassWalk = false) {
		//================================================================
		//fig:macro
		//There is no condition to a macro definition.
		//A tag bearing the fig:macro directive is not implied mute.
		//It can render as a regular outer tag. If needed, it must be explicitly muted.
		if($this->hasAttribute($this->view->figNamespace . 'macro')) {
			return $this->fig_macro();
		}

		return $this->renderNoMacro($bypassWalk);
	}
	private function renderNoMacro($bypassWalk = false) {

		//Reset the switch status of the element,
		//so that its immediate children can run the fig:case attribute form fresh.
		$this->caseSwitched = false;

		//================================================================
		//fig:cond
		//(fig:condition is deprecated)
		//If the tag holds a fig:walk directive as well as the fig:cond,
		//do not take into account this fig:cond unless the rendering is inside the looping phase,
		//because the condition pertains to every single iteration, rather than to the global loop.
		if($this->hasAttribute($this->view->figNamespace . 'cond')) {
			if(! ($this->hasAttribute($this->view->figNamespace . 'walk') && $bypassWalk) ) {
				if(! $this->evalCondition()) {
					return '';
				}
			}
		}

		//================================================================
		//fig:case
		//Warning: a fig:case on a tag that has also a fig:plug :
		//because the plugged node is a reference to the current node,
		//when the plug is stuffed into its slot, the parent of the plugged node
		//reports that it was caseSwitched already.
		if(isset($this->attributes[$this->view->figNamespace . 'case'])) {
			if($this->parent) {
				if($this->parent->caseSwitched) {
					return '';
				}
				else {
					$condExpr = $this->attributes[$this->view->figNamespace . 'case'];
					$condVal = $this->evaluate($condExpr);
					if(! $condVal) {
						return '';
					}
					else {
						$this->parent->caseSwitched = true;
					}
				}
			}
		}

		//================================================================
		//fig:feed
		if($this->name == $this->view->figNamespace . 'feed') {
			$this->fig_feed();
			return '';
		}

		//================================================================
		if($this->name == $this->view->figNamespace . 'mount') {
			$this->fig_mount();
			return '';
		}

		//================================================================
		//fig:include
		if( ($this->name == $this->view->figNamespace . 'include') && ! isset($this->bRendering) ) {
			return $this->fig_include();
		}

		//================================================================
		//fig:cdata
		if( ($this->name == $this->view->figNamespace . 'cdata') && ! isset($this->bRendering) ) {
			return $this->fig_cdata();
		}

		//================================================================
		//fig:trans
		if($this->name == $this->view->figNamespace . 'trans') {
			return $this->fig_trans();
		}
		//================================================================
		//fig:dictionary
		if($this->name == $this->view->figNamespace . 'dictionary') {
			return $this->fig_dictionary();
		}

		//================================================================
		//fig:attr
		//Add to the parent tag the given attribute. Do not render.
		if( $this->name == $this->view->figNamespace . 'attr' ) {
			if(!isset($this->attributes['name'])) {
				//name is a required attribute for fig:attr.
				throw new RequiredAttributeException($this->view->figNamespace . 'attr', $this->currentFile->getFilename(), $this->xmlLineNumber, 'Missing required name attribute for attr tag.');
			}
			if(isset($this->attributes['value'])) {
				$value = $this->evaluate($this->attributes['value']);
				if(is_string($value)) {
					$value = htmlspecialchars($value);
				}
				$this->parent->runtimeAttributes[$this->attributes['name']] = $value;
			}
			else {
				$value = '';
				/**
				 * @var ViewElement
				 */
				$child = null;
				foreach ($this->children as $child) {
					$renderChild = $child->render();
					if($renderChild === false) {
						throw new Exception();
					}
					$value .= $renderChild;
				}
				//An XML attribute should not span accross several lines.
				$value = trim(preg_replace("#[\n\r\t]+#", ' ', $value));
				$this->parent->runtimeAttributes[$this->attributes['name']] = $value;
			}
			return '';
		}



		//================================================================
		//fig:walk
		//Loop over evaluated dataset.
		if(! $bypassWalk) {
			if($this->hasAttribute($this->view->figNamespace . 'walk')) {
				return $this->fig_walk();
			}
		}

		//================================================================
		//fig:call
		//A tag with the fig:call directive is necessarily mute.
		//It is used as a placeholder only for the directive.
		if(isset($this->attributes[$this->view->figNamespace . 'call'])) {
			return $this->fig_call();
		}




		//================================================================
		//fig:slot
		//Define the named slot at View level, pointing on the
		//current position of the rendering output.
		//Then, render the slot element, as a default content in case
		//nothing is plugged into it.
		//In case of plug for this slot, the complete slot tag (outer) is replaced.
		if(isset($this->attributes[$this->view->figNamespace . 'slot'])) {
			//Extract name of slot
			$slotName = $this->attributes[$this->view->figNamespace . 'slot'];
			//Store a reference to current node, into the View's map of slots

			$anchorString = '/==SLOT==' . $slotName . '==/';
			$slot = new Slot($anchorString);
			$this->view->assignSlot($slotName, $slot);

			unset($this->attributes[$this->view->figNamespace . 'slot']);
			$result = $this->render();
			if($result === false)
				throw new Exception();
			$slot->setLength(strlen($result));
			$this->attributes[$this->view->figNamespace . 'slot'] = $slotName;
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
		if(isset($this->attributes[$this->view->figNamespace . 'plug'])) {
			$slotName = $this->attributes[$this->view->figNamespace . 'plug'];

			//Keep track of the callback node (the fig:plug node).
			//The callbacks are maintained as a chain. Several callbacks
			//can enchain one after the other,
			//in the order they were parsed.
			$this->view->addPlug($slotName, $this);

			//The slots are rendered at the end of the rendering of the View.
			//A fig:plug tag does not produce any in-place output.
			return '';
		}



		//================================================================
		//fig:auto
		//
		//This attribute means that the tag should render auto-closed, even though it
		//may be exploded for the purpose of assigning fig:attr children.
		if(isset($this->attributes[$this->view->figNamespace . 'auto'])) {
			$expression = $this->attributes[$this->view->figNamespace . 'auto'];
			if($this->evaluate($expression)) {
				$this->attributes[$this->view->figNamespace . 'text'] = '';
				$this->autoclose = true;
			}
		}

		//================================================================
		//fig:void
		//
		//This attribute means that the tag should render as HTML-void (such as: <br>)
		//and its contents discarded.
		if(isset($this->attributes[$this->view->figNamespace . 'void'])) {
			$expression = $this->attributes[$this->view->figNamespace . 'void'];
			if($this->evaluate($expression)) {
				$this->attributes[$this->view->figNamespace . 'text'] = '';
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
		if(isset($this->attributes[$this->view->figNamespace . 'text'])) {
			$content = & $this->attributes[$this->view->figNamespace . 'text'];

			$output = $this->evaluate($content);
			if($output === null) {
				$this->outputBuffer = '';
			}
			else {
				$this->outputBuffer = $output;
				if (trim($output) != '') {
					//We clear the autoclose flag only if there is any meaningful
					//inner content.
					$this->autoclose = false;
				}
			}

			if(! $this->isMute()) {
				//Take care of inner fig:attr
				for($iChild = 0; $iChild < count($this->children); ++$iChild) {
					$child = & $this->children[$iChild];
					if($child instanceof TagFigAttr) {
						$child->render();
					}
				}
			}
		}


		//Now proceed with the children...
		$result = $this->renderChildren();



		//Let's apply the potential filter on the inner parts.
		$result = $this->applyOutputFilter($result);

		//And then, render the outer XML part of the tag, if not mute.

		//fig:mute
		//If this attribute is set and true,
		//then the current element is rendered withouts its
		//outer tag.
		//Use short-circuit test if no fig:mute attribute, in order not
		//to evaluate needlessly.
		//Every fig: tag is mute by nature.
		if(! $this->isMute()) {
			$xmlAttributesString = $this->buildXMLAttributesString();

			if ($this->voidtag) {
				$result = '<' . $this->name . $xmlAttributesString . '>';
			}
			else {
				if($result instanceof ViewElementTag) {
					$innerResults = $result->render();
				}
				else {
					$innerResults = $result;
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
			$this->runtimeAttributes = array();
		}

		if($this->view->error) {
			throw new Exception();
		}

		return $result;
	}

	private function renderChildren($doNotRenderFigParam = false) {
		$result = '';
		//If a fig treatment happened already, then outputBuffer contains
		//the result to use. Otherwise, it needs to be calculated recursively
		//with the children.
		if($this->outputBuffer === null) {
			//A mute tag that does not have any content rendered yet, should not output the consecutive blank cdata.
			if( $this->isMute() ) {
				if(isset($this->children[0]) && ($this->children[0] instanceof ViewElementCData) ) {
					//TODO: comprendre pourquoi il me reste des blancs avant ?xml
					$this->children[0]->outputBuffer = ltrim($this->children[0]->outputBuffer);
				}
			}

			for($iChild = 0; $iChild < count($this->children); ++$iChild) {
				$child = & $this->children[$iChild];
				if($doNotRenderFigParam && ($child instanceof ViewElementTag) && ($child->getTagName() == $this->view->figNamespace . 'param') ) {
					//This situation is encountered when a sourced fig:trans tag has a fig:param immediate child:
					//the fig:param is used to resolve the translation, but it must not be rendered in the value of the
					//fig:trans tag (because it is sourced, it means that the translation is not to be taken from an external dictionary,
					//but rather directly the rendered content of the fig:trans tag).
					continue;
				}

				$subRender = $child->render();
				//Caution: it used to work fine, and then I got a: Could not
				//convert ViewElementTag to string,
				//as I called a macro with a fig:param being some XML piece, instead of a plain value.
				//So I added this additional step: the rendering of the said ViewElementTag.
				if($subRender instanceof ViewElement) {
					$subRender = $subRender->render();
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
	 * @param $name Attribute name
	 * @return mixed
	 */
	private function evalAttribute($name) {
		$expression = $this->getAttribute($name, false);
		if($expression) {
			return $this->evaluate($expression);
		}
		return false;
	}

	public function getAttribute($name, $default = null) {
		if(isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		return $default;
	}

	private function fig_mount() {
		$target = $this->getAttribute('target');
		//TODO: $anchor = $this->getAttribute('anchor');
		//When an explicit value="" attribute exists, use its contents as a Lex expression to evaluate.
		if($this->hasAttribute('value')) {
			$valueExpression = $this->getAttribute('value');
			$value = $this->evaluate($valueExpression);
		}
		//Otherwise, no value attribute: then we render the inner contents of the fig:mount into the target variable.
		else {
			$value = $this->renderChildren(true);
		}

		$this->view->mount($target, $value);
	}

	/**
	 * Process <fig:feed> tag.
	 * This tag accepts the following attributes:
	 *  - class = the name of the Feed class to instanciate and run.
	 *  - target = the mount point in the global universe.
	 *
	 * @access private
	 * @return void
	 */
	private function fig_feed() {
		if($this->logger == null) {
			$this->logger = LoggerFactory::getLogger(get_class($this));
		}

		$className = isset($this->attributes['class']) ? $this->attributes['class'] : null;
		if(null === $className) {
			$this->logger->error('Missing class attribute for fig:feed.');
			throw new RequiredAttributeException($this->getTagName(), $this->getCurrentFile()->getFilename(), $this->xmlLineNumber, 'Missing "class" attribute for fig:feed tag, in ' . $this->getCurrentFile()->getFilename() . '(' . $this->xmlLineNumber . ')');
		}

		//Set the parameters for the feed class:
		//the parameters are an assoc array made of the
		//scalar attributes of the fig:feed tag other than fig:* and
		//class and target attributes.
		$feedParameters = array();
		foreach($this->attributes as $attribName=>$attribText) {
			if( (! $this->view->isFigAttribute($attribName)) && 
					($attribName != 'class') && ($attribName != 'target') ) {
				$feedParameters[$attribName] = $this->evaluate($attribText);
			}
		}

		//TODO: catch exception, to enrich with fig xml file+line, and rethrow.
		$feedInstance = $this->view->createFeed($className, $feedParameters);

		//At this point the feed instance must be created.
		//If not, there was no factory to handle its loading.
		if(! $feedInstance) {
			throw new FeedClassNotFoundException($className, $this->getCurrentFile()->getFilename(), $this->xmlLineNumber);
		}

		//It is possible to simply invoke a Feed class and
		//discard its result, by not defining a target to the tag.
		$mountPoint = null;
		if(isset($this->attributes['target'])) {
			$mountPoint = $this->attributes['target'];
		}


		//TODO: check if it still always needed to know the fig file from within the feed.
		//$feedInstance->setFigFile($this->view->getFilename());

		$feedInstance->setParameters($feedParameters);

		$subUniverse = null;

		try {
			$subUniverse = $feedInstance->run();
		}
		catch(FeedRuntimeException $ex) {
			//Do nothing: we certainly don't want
			//to populate the data universe.
			return '';
		}

		if($mountPoint !== null) {
			$this->view->mount($mountPoint, $subUniverse);
		}

		return '';
	}

	/**
	 * Imports at the current output position
	 * the contents of specified file unparsed, rendered as is.
	 * @return string
	 * @throws FileNotFoundException
	 */
	private function fig_cdata() {
		$filename = $this->attributes['file'];
		$realfilename = realpath(dirname($this->getCurrentFile()->getFilename()).'/'.$filename);
		if(! file_exists($realfilename)) {
			$message = "File not found: $filename called from: " . $this->getCurrentFile()->getFilename(). '(' . $this->xmlLineNumber . ')';
			$this->getLogger()->error($message);
			throw new FileNotFoundException($message, $filename);
		}
		$cdata = file_get_contents($realfilename);
		return $cdata;
	}

	/**
	 * Creates a sub-view object, invokes its parsing phase,
	 * and renders it as the child of the current tag.
	 * @return string or false
	 */
	private function fig_include() {
		//Extract from the attributes the file to include.
		if (! $this->hasAttribute('file')) {
		  throw new RequiredAttributeException($this->name,
		    $this->getCurrentFilename(),
		    $this->getLineNumber(), 
		    'Missing required attribute: "file" in tag: "'. $this->name .'"' .
		      ' in file: ' .$this->getCurrentFilename(). '(' .$this->getLineNumber(). ')');
		}
		$file = $this->attributes['file'];

		//Create a sub-view, attached to the current element.
		$view = new View();
		$view->inherit($this);
		$view->loadFile(dirname($this->getCurrentFile()->getFilename()).'/'.$file, $this->getCurrentFile());

		//Make the current node aware that it is being rendered
		//as an include directive (therefore, it will be skipped
		//when the subview tries to render it).
		$this->bRendering = true;

		//Parse the subview (build its own tree).
		$view->parse();

		$result = $view->render();
		unset($this->bRendering);
		return $result;
	}


	/**
	 * Defines a macro in the dictionary of the
	 * topmost view.
	 */
	private function fig_macro() {
		$macroName = $this->attributes[$this->view->figNamespace . 'macro'];
		$this->view->macros[$macroName] = & $this;
		return '';
	}

	/**
	 * Renders the call to a macro.
	 * No need to hollow the tag that bears the fig:call attribute,
	 * because the output of the macro call replaces completely the
	 * whole caller tag.
	 * @return string
	 */
	private function fig_call() {
		//Retrieve the name of the macro to call.
		$macroName = $this->attributes[$this->view->figNamespace . 'call'];

		//Prepare the arguments to pass to the macro:
		//all the non-fig: attributes, evaluated.
		$arguments = array();
		foreach($this->attributes as $attribName => $attribValue) {
			if( ! $this->view->isFigAttribute($attribName) ) {
				$value = $this->evaluate($attribValue);
				$arguments[$attribName] = $value;
			}
		}


		//Fetch the parameters specified as immediate children
		//of the macro call : <fig:param name="" value=""/>
		$arguments = array_merge($arguments, $this->collectParamChildren());

		//Retrieve the macro contents.
		if(isset($this->view->macros[$macroName])) {
			$macroElement = & $this->view->macros[$macroName];
			$this->view->pushStackData($arguments);
			if(isset($this->iteration)) {
				$macroElement->iteration = &$this->iteration;
			}

			//Now render the macro contents, but do not take into account the fig:macro
			//that its root tag holds.
			$result = $macroElement->renderNoMacro();
			$this->view->popStackData();
			return $result;
			//unset($macroElement->iteration);
		}
		return '';
	}

	/**
	 * Returns an array of named values obtained by the immediate :param children of the tag.
	 * The array is to be merged with the other arguments provided by inline 
	 * attributes of the :call tag. 
	 * @return array
	 */
	private function collectParamChildren() {
		$arguments = array();

		//Fetch the parameters specified as immediate children
		//of the macro call : <fig:param name="" value=""/>
		//TODO: <fig:param> can hold fig:cond ok, but we must implement no fig:case
		foreach ($this->children as $child) {
			if($child instanceof ViewElementTag) {
				if($child->name == $this->view->figNamespace . 'param') {

					//evalute the fig:cond on the param
					if(! $child->evalCondition()) {
						continue;
					}
					//If param is specified with an immediate value="" attribute :
					if(isset($child->attributes['value'])) {
						$arguments[$child->attributes['name']] = $this->evaluate($child->attributes['value']);
					}
					//otherwise, the actual value is not scalar but is
					//a nodeset in itself. Let's pre-render it and use it as text for the argument.
					else {
						$arguments[$child->attributes['name']] = $child->render();
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
	 * @param string $buffer the inner contents of the element, after rendering.
	 * @return string
	 * 
	 */
	private function applyOutputFilter($buffer) {
		//TODO: Currently the filtering works only on non-slot tags.
		//If applied on a slot tag, the transform is made on the special placeholder /==SLOT=.../
		//rather than the future contents of the slot.
		if(isset($this->attributes[$this->view->figNamespace . 'filter'])) {
			$filterClass = $this->attributes[$this->view->figNamespace . 'filter'];
			$filter = $this->instanciateFilter($filterClass);
			$buffer = $filter->transform($buffer);
		}
		return $buffer;
	}
	/**
	 * Iterates the rendering of the current element,
	 * over the data specified in the fig:walk attribute.
	 *
	 * @return string
	 */
	private function fig_walk() {
		$figIterateAttribute = $this->attributes[$this->view->figNamespace . 'walk'];
		$dataset = $this->evaluate($figIterateAttribute);

		//Walking on nothing gives no ouptut.
		if(null === $dataset) {
			return '';
		}

		$dataBackup = $this->data;
		$outputBuffer = '';

		if(is_object($dataset) && ($dataset instanceof Iterable) ) {
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

		if(!isset($this->iteration) || ($this->iteration == null) ) {
			$this->iteration = array();
		}
		$newIteration = new Iteration($datasetCount);
		array_push($this->iteration, $newIteration);
		$bFirstIteration = true;

		if(is_array($dataset) || (is_object($dataset) && ($dataset instanceof Iterable)) ) {
			foreach($dataset as $key => $data) {
				$this->view->pushStackData($data);
				$newIteration->iterate($key);
				$nextContent = $this->render(/*bypassWalk*/true);
				if($nextContent === false) {
					throw new RenderingException($this->getTagName(),
							$this->getCurrentFilename(),
							$this->getLineNumber(),
							"In file: " . $this->getCurrentFilename() . '(' . $this->getLineNumber() . '), '.
							'tag <' . $this->getTagName() . '> : ' . PHP_EOL .
							"Inner content of loop could not be rendered." . PHP_EOL .
								"Have you used :walk and :text on the same tag?");
				}

				//Each rendered iteration start again
				//with the blank part on the right of the preceding CDATA,
				//so that the proper indenting is kept, and carriage returns
				//between each iteration, if applies.
				if(! $bFirstIteration) {
					if($this->previousSibling) {
						if($this->previousSibling instanceof ViewElementCData) {
							if(($rtrim = rtrim($this->previousSibling->outputBuffer)) < $this->previousSibling->outputBuffer) {
								$blankRPart = substr($this->previousSibling->outputBuffer, strlen($rtrim));
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
				$this->view->popStackData();
			}
		}
		$this->data = &$dataBackup;
		array_pop($this->iteration);
		return $outputBuffer;
	}

	/**
	 * Loads a language XML file, to be used within the current view.
	 */
	private function fig_dictionary() {
		//If a @source attribute is specified,
		//it means that when the target (view's language) is the same as @source,
		//then don't bother loading dictionary file, nor translating: just render the tag's children.

		$file = $this->attributes['file'];
		$filename = $this->getView()->getTranslationPath() . DIRECTORY_SEPARATOR . $this->getView()->getLanguage() . DIRECTORY_SEPARATOR . $file;

		$name = $this->getAttribute('name', null);
		$dictionary = new Dictionary($filename);
		$source = $this->getAttribute('source', null);

		if( $source == $this->getView()->getLanguage() ) {
			return '';
		}

		//TODO: Please optimize here: cache the realpath of the loaded dictionaries,
		//so as not to re-load an already loaded dictionary in same View hierarchy.


		try {
			//Determine whether this dictionary was pre-compiled:
			if($this->getView()->getTempPath()) {
				$tmpFile = $this->getView()->getTempPath() . DIRECTORY_SEPARATOR . 'Dictionary' . DIRECTORY_SEPARATOR . $this->getView()->getLanguage() . DIRECTORY_SEPARATOR . $file . '.php';
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
			throw new FileNotFoundException('Translation file not found: file=' . $filename . ', language=' . $this->getView()->getLanguage() . ', source=' . $this->getCurrentFile()->getFilename(), $this->getCurrentFile()->getFilename() );
		} catch(DictionaryDuplicateKeyException $ddkex) {
			$this->getLogger()->error('Duplicate key: "' . $ddkex->getKey() . '" in dictionary: ' . $ddkex->getFilename());
		}


		//Hook the dictionary to the current file.
		//(in fact this will bubble up the message as high as possible, ie:
		//to the highest parent which does not bear a dictionary of same name)
		$this->getCurrentFile()->addDictionary($dictionary, $name);
		return '';
	}

	/**
	 * Translates a caption given its key and dictionary name.
	 * @return string
	 */
	private function fig_trans() {

		//If a @source attribute is specified, and is equal to
		//the view's target language, then don't bother translating:
		//just render the contents.
	  $source = $this->getAttribute('source', null);
		
	  //The $key is also needed in logging below, even if
	  //source = view's language, in case of missing value,
	  //so this is a good time to read it.
	  $key = $this->getAttribute('key', null);

		if($source == $this->getView()->getLanguage()) {
			$value = $this->renderChildren(true /*Do not render fig:param immediate children */);
		}

		else {
			//Cross-language dictionary mechanism:

			if(null == $key) {
				//Missing @key attribute : consider the text contents as the key.
				//throw new SyntaxErrorException($this->getCurrentFile()->getFilename(), $this->xmlLineNumber, $this->name, 'Missing @key attribute.');
				$key = $this->renderChildren();
			}
			//Ask current file to translate key:
			$dictionaryName = $this->getAttribute('dict', null);
			try {
				$value = $this->getCurrentFile()->translate($key, $dictionaryName);
			} catch(DictionaryEntryNotFoundException $ex) {
				LoggerFactory::getLogger('Dictionary')->error('Translation not found: key=' . $key . ', dictionary=' . $dictionaryName . ', language=' . $this->getView()->getLanguage() . ', file=' . $this->getCurrentFile()->getFilename() . ', line=' . $this->xmlLineNumber);
				return $key;
			}
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
						$arguments[$child->attributes['name']] = $this->evaluate($child->attributes['value']);
					}
					//otherwise, the actual value is not scalar but is
					//a nodeset in itself. Let's pre-render it and use it as text for the argument.
					else {
						$arguments[$child->attributes['name']] = $child->render();
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
				//Data coming from my database can contain accented ascii chars (like � : chr(233)),
				//which may corrupt the subsequent: return utf8_decode($value) at the end of this function.
				$attributeValue = utf8_encode($arguments[$attributeName]);
			}
			//Otherwise, use the inline attribute.
			else {
				$attributeValue = $this->evalAttribute($attributeName, null);
			}
			$value = str_replace('{' . $attributeName . '}', $attributeValue, $value);
		}

		//TODO: les param�tres de traduction pass�s en fig:param plut�t.
		//Ce mode est important pour passer des param�tres conditionnels, par exemple
		//(bien que je suppose que pour des traductions c'est maladroit).

		//If the translated value is empty (ie. we did find an entry in the proper dictionary file,
		//but this entry has an empty value), it means that the entry remains to be translated by the person in charge.
		//So in the meantime we output the key.
		if($value == '') {
			$value = $key;
			LoggerFactory::getLogger('Dictionary')->error('Empty translation: key=' . $key . ', dictionary=' . $dictionaryName . ', language=' . $this->getView()->getLanguage() . ', file=' . $this->getCurrentFile()->getFilename() . ', line=' . $this->xmlLineNumber);
		}
		//TODO: There are some situations where the resulting value must be decoded... Why??
		//return utf8_decode($value);
		return $value;
	}

	/**
	 * Returns the Iteration object to which the current tag
	 * is related, if applies. 
	 *
	 * @return Iteration
	 */
	public function getIteration() {
		$current = & $this;
		while($current) {
			//The iteration property is either undefined,
			//or an array. If an array, it can be empty.
			//All these mean that the tag is not itself in an 
			//iterating process.
			//But if iteration property is a non-empty array,
			//use its end-most element. 
			if(isset($current->iteration) && (count($current->iteration))) {
				return $current->iteration[count($current->iteration) - 1];
			}

			$current = & $current->parent;
		}
		return new Iteration(0);
	}

	/**
	 * Determines whether the current Tag bears the mute/hollow directive,
	 * in which case it should render only its inner parts,
	 * without an outer tag.
	 * A <fig: > tag is always mute.
	 *
	 * @return boolean
	 */
	private function isMute() {
		if($this->view->isFigAttribute($this->name)) {
			return true;
		}

		$expression = '';
		if(isset($this->attributes[$this->view->figNamespace . 'mute']))
			$expression = $this->attributes[$this->view->figNamespace . 'mute'];
		if($expression)
			return $this->evaluate($expression);
		return false;
	}

	/**
	 * @return Filter
	 */
	private function instanciateFilter($className) {
		if(! class_exists($className)) {
			$classFile = $className . '.php';
			if(realpath($classFile) != $classFile)
				$classFile = $this->view->getFilterPath() . '/' . $classFile;
			if(! file_exists($classFile)) {
				$message = 'Filter file not found: ' . $classFile . ' in Fig source: ' . $this->currentFile->getFilename() . "({$this->xmlLineNumber})";
				$this->getLogger()->error($message);
				throw new Exception($message);
			}

			require_once $classFile;

			//Check that the loaded file did declare the requested class:
			if(! class_exists($className)) {
				$this->getLogger()->error("Undefined filter class: $className in file: $classFile");
				throw new Exception();
			}
		}

		if($this->view->getFilterFactory())
			return $this->view->getFilterFactory()->create($className);

		$reflection = new ReflectionClass($className);
		return $reflection->newInstance();
	}

	/**
	 * Returns the logger instance,
	 * or creates one beforehand, if null.
	 *
	 * @return LoggerInterface
	 */
	private function getLogger() {
		if(! $this->logger) {
			$this->logger = LoggerFactory::getLogger(get_class($this));
		}
		return $this->logger;
	}
	/**
	 * The tag name. 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Indicates whether the XML element is to be rendered as html-void.
	 * This is useful in order to comply to the various (x)HTML doctypes.
   * Example:
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
   * @return boolean
   */
	public function getVoidTag() {
		return $this->voidtag;
	}
}
