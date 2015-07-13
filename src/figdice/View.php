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

namespace figdice;

use figdice\classes\NativeFunctionFactory;
use figdice\classes\TagFigAttr;
use figdice\classes\File;
use figdice\classes\ViewElementTag;
use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\XMLParsingException;

use Psr\Log\LoggerInterface;
use figdice\exceptions\RenderingException;
use figdice\classes\XMLEntityTransformer;
use figdice\classes\Slot;

/**
 * Main component of the FigDice library.
 * The View object represents the transform lifecycle from a template into a rendered document.
 *
 * After creating a View instance, you may register one or more {@link FeedFactory} objects (see {@see registerFeedFactory}),
 * and you may register one or more {@link FunctionFactory} object (see {@see registerFunctionFactory}) and one {@see FilterFactory} (see {@see setFilterFactory}).
 *
 * Then you would {@see loadFile} an XML source file, and finally {@see render} it to obtain its final result (which you would typically output to the browser).
 */
class View {
	/**
	 * Textual source of the transformation.
	 *
	 * @var string
	 */
	private $source;

	/**
	 * The file of the view.
	 * A View can be actually a subview, invoked by the fig:include directive.
	 * In this case, the View's File represents the included file.
	 * @var File
	 */
	private $file;

	/**
	 * @var LoggerInterface
	 */
	public $logger;

	/**
	 * The path where to find the output filters for the view.
	 * @var string
	 */
	private $filterPath;
	/**
	 * The directory where to store temporary files (results of compilation).
	 * @var string
	 */
	private $tempPath;
	/**
	 * The Filter Factory instance.
	 * @var FilterFactory
	 */
	private $filterFactory;
	/**
	 * The path where to find the language packs.
	 * They are organized in the following tree:
	 * <code>
	 *   <translationPath>/<languageCode>/...(the dictionary files, named the same in every languageCode)
	 * </code>
	 * @var string
	 */
	private $translationPath;
	/**
	 * The language in which to translate the view,
	 * using fig:trans tags.
	 * @var string
	 */
	private $language;

	/**
	 * Depth stack used at parse time.
	 *
	 * @var array
	 */
	private $stack;

	/**
	 * Topmost node of the tree after successful parsing.
	 *
	 * @var ViewElement
	 */
	private $rootNode;

	/**
	 * @var boolean
	 */
	private $replacements = true;

	/**
	 * Indicates whether the source file was successfully parsed.
	 * @var boolean
	 */
	private $bParsed;

	/**
	 * XML parser resource (domxml)
	 *
	 * @var XML_ressource
	 */
	private $xmlParser;

	/**
	 * When this View is not created directly by the user
	 * (ie when it is a sub-view of another view, invoked
	 * by the fig:include directive), this variable refers
	 * to the fig:include ViewElementTag of the parent view.
	 *
	 * Every ViewElement created subsequently during the
	 * parsing phase of this View, is attached to the caller view,
	 * so as to inject the parsed elements directly into the tree
	 * of the caller view.
	 * @var ViewElementTag
	 */
	public $parentViewElement;


	/**
	 * The array of named slots defined in the view.
	 * A slot is a specific ViewElementTag identified
	 * by a name, whose content is replaced by the content
	 * of the element that has been pushed (plugged) into the slot.
	 * @var array ({@link Slot})
	 */
	private $slots;

	/**
	 * Array of named elements that are used as content providers
	 * to fill in slots by the same name.
	 * @var array (ViewElement)
	 */
	private $plugs;

	/**
	 * Associative array of the named macros.
	 * A Macro is a ViewElementTag that is rendered
	 * only upon calling from within another element.
	 *
	 * @var array ( ViewElementTag )
	 */
	public $macros;


	public $error;

	/**
	 * Used internally at parse-time to determine whether when the very
	 * first tag opening occurs.
	 * @var boolean
	 */
	private $firstOpening;
	/**
	 * Used internally with $firstOpening, to help detecting the auto-closing tags.
	 * @var integer
	 */
	private $firstTagOffset;

	/**
	 * Cache of Lexers.
	 * @var array of Lexer
	 */
	public $lexers;

	/**
	 * The FeedFactory objects passed by the caller of the view,
	 * which are used to instanciate the feeds.
	 * @var array of FeedFactory
	 */
	public $feedFactories = array();

	/**
	 * Cache mechanism for feed instanciation.
	 * @var array of classname => factory object
	 */
	private $feedFactoryForClass = array();

	/**
	 * The data available to the FIG tags, arranged in stack
	 * of called macros. The head element si the top-level universe.
	 *
	 * @var array
	 */
	private $callStackData;

	/**
	 * Array of FunctionFactory, used to generate the Function handlers in the Lexer.
	 *
	 * @var FunctionFactory[]
	 */
	private $functionFactories;

	/**
	 * Can be overridden with xmlns:XYZ="http://www.figdice.org/"
	 * @var string
	 */
	public $figNamespace = 'fig:';

	public function __construct() {
		$this->source = '';
		$this->result = '';
		$this->rootNode = null;
		$this->stack = array();
		$this->logger = LoggerFactory::getLogger(get_class($this));
		$this->parentViewElement = null;
		$this->lexers = array();
		$this->callStackData = array(array());
		$this->functionFactories = array(new NativeFunctionFactory());
		$this->language = null;
	}

	/**
	 * 
	 * Specifies the target language code in which you wish to translate 
	 * the fig:trans tags or your templates.
	 * This language code must correspond to a subfolder of the Translation Path
	 * Set to null in order to specify that the view should not try any translation.
	 * @param $language string
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}
	/**
	 * Returns the language in which the view is to be translated
	 * if any fig: translation resources are specified.
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}
	/**
	 * When including a fig file, the included entity
	 * is processed as a View in itself.
	 * Yet, it is linked to the parent View.
	 *
	 * @param ViewElementTag $parentViewElement
	 */
	public function inherit(ViewElementTag &$parentViewElement) {
		$this->parentViewElement = & $parentViewElement;
		$this->callStackData = & $parentViewElement->view->callStackData;
		$this->functionFactories = & $parentViewElement->view->functionFactories;
		$this->feedFactories = & $parentViewElement->view->feedFactories;
		$this->feedFactoryForClass = & $parentViewElement->view->feedFactoryForClass;
		$this->replacements = $parentViewElement->view->replacements;
	}

	/**
	 * Register a new Function Factory instance,
	 * that the Lexer will use in order to load a function handler class.
	 *
	 * @param FunctionFactory $factory
	 */
	public function registerFunctionFactory(FunctionFactory $factory) {
		array_unshift($this->functionFactories, $factory);
	}
	/**
	 * Returns the registered Function Factories.
	 *
	 * @return FunctionFactory[]
	 */
	public function getFunctionFactories() {
		return $this->functionFactories;
	}
	/**
	 * Register a new Feed Factory instance.
	 *
	 * @param FeedFactory $factory
	 */
	public function registerFeedFactory(FeedFactory $factory) {
		$this->feedFactories []= $factory;
	}

	/**
	 * Load from source file.
	 *
	 * @param string $filename
	 * @throws FileNotFoundException
	 */
	public function loadFile($filename, File $parent = null) {
		$this->file = new File($filename, $parent);

		if(file_exists($filename)) {
			$this->source = file_get_contents($filename);
		}
		else {
			$message = "File not found: $filename";
			$this->logger->error($message);
			throw new FileNotFoundException($message, $filename);
		}
	}

	/**
	 * Instead of loading a file, you can load a string, and optionally pass
	 * a "working directory" (mainly useful for incldues).
	 * This creates a File object with no real filesystem location.
	 * @internal
	 * @param string $string
	 */
	public function loadString($string, $workingDirectory = null) {
	  $this->file = new File($workingDirectory . '/(null)');
	  $this->source = $string;
	}
	/**
	 * @return ViewElementTag
	 */
	public function getRootNode() {
		return $this->rootNode;
	}
	
	/**
	 * Specifies whether the View should replace
	 * the standard HTML entities with their special character equivalelent
	 * in source before parsing. Default: true
	 * You should not have to turn off the replacements, unless
	 * working in HHVM and intending to produce non-HTML output, from a
	 * source text which still contains HTML escape sequences (like &auml;)
	 * @param boolean $bool
	 */
	public function setReplacements($bool) {
	    $this->replacements = $bool;
	}
	/**
	 * Parse source.
	 * @return void
	 * @throws XMLParsingException
	 */
	public function parse() {
		if($this->bParsed) {
			return;
		}

		if ($this->replacements) {
        // We cannot rely of html_entity_decode,
        // because it would replace &amp; and &lt; as well,
        // whereas we need to keep them unmodified.
        // We must do it manually, with a modified hardcopy 
        // of PHP 5.4+ 's get_html_translation_table(ENT_XHTML)
        $this->source = XMLEntityTransformer::replace($this->source);
		}
		
		$this->xmlParser = xml_parser_create('UTF-8');
		xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object($this->xmlParser, $this);
		xml_set_element_handler($this->xmlParser, 'openTagHandler', 'closeTagHandler');
		xml_set_character_data_handler($this->xmlParser,'cdataHandler');

		//Prepare the detection of the very first tag, in order to compute
		//the offset in XML string as regarded by the parser.
		$this->firstOpening = true;
		$bSuccess = xml_parse($this->xmlParser, $this->source);

		if(!$bSuccess) {
			$errMsg = xml_error_string(xml_get_error_code($this->xmlParser));
			$lineNumber = xml_get_current_line_number($this->xmlParser);
			if(count($this->stack)) {
				$lastElement = $this->stack[count($this->stack) - 1];
				if($lastElement instanceof ViewElementTag) {
					$errMsg .= '. Last element: ' . $lastElement->getName();
				}
			}
			$this->errorMessage($errMsg);
		}

		xml_parser_free($this->xmlParser);
		$this->bParsed = $bSuccess;

		if(! $bSuccess ) {
			throw new XMLParsingException(
					$errMsg,
					($this->file ? $this->file->getFilename() : '(null)'),
					$lineNumber);
		}
	}

	/**
	 * Process parsed source and render view,
	 * using the data universe.
	 *
	 * @return string
	 * @throws RenderingException
	 */
	public function render() {
		if(! $this->bParsed) {
			$this->parse();
		}


		if (null != $this->parentViewElement) {
			$this->rootNode->view = & $this->parentViewElement->view;
		}

		if (! $this->rootNode) {
			throw new XMLParsingException('No template file loaded', '', 0);
		}
		$result = $this->rootNode->render();


		if(! $this->parentViewElement) {
			$result = $this->plugIntoSlots($result);
		}

		return $result;
	}

	/**
	 * Specifies the folder in which the engine will be able to produce 
	 * temporary files, for JIT-compilation and caching purposes. The engine will 
	 * not attempt to use cache-based optimization features if you leave 
	 * this property blank.
	 * @param string $path
	 */
	public function setTempPath($path) {
		$this->tempPath = $path;
	}
	/**
	 * The folder in which FigDice can store its cache
	 * (currently only Dictionary cache)
	 * @return string
	 */
	public function getTempPath() {
		return $this->tempPath;
	}
	/**
	 * @return string
	 */
	public function getFilterPath() {
		return $this->filterPath;
	}
	/**
	 * Specify the default location for all the filters
	 * invoked by the view.
	 *
	 * @param string $path
	 */
	public function setFilterPath($path) {
		$this->filterPath = $path;
	}
	/**
	 * Returns the Filter Factory instance attachted to the view.
	 *
	 * @return FilterFactory
	 */
	public function getFilterFactory() {
		return $this->filterFactory;
	}
	/**
	 * Sets the Filter Factory instance.
	 *
	 * @param FilterFactory $filterFactory
	 */
	public function setFilterFactory($filterFactory) {
		$this->filterFactory = $filterFactory;
	}

	/**
	 * Specify rendering data by name.
	 * Can be used in replacement to the fig:feed tag and its Feed class.
	 * Always mounts the data at the root level of the universe.
	 *
	 * @param string $mountingName
	 * @param mixed $data
	 */
	function mount($mountingName, $data) {
		$this->callStackData[0][$mountingName] = $data;
	}


	private function openTagHandler($xmlParser, $tagName, $attributes) {
		if($this->firstOpening) {
			$this->firstOpening = false;
			$positionOfFirstTag = xml_get_current_byte_index($xmlParser);
			$this->firstTagOffset = strpos($this->source, "<$tagName") - $positionOfFirstTag;

			//Position the namespace:
			$matches = null;
			foreach($attributes as $attrName => $attrValue) {
				if(preg_match('/figdice/', $attrValue) &&
					 preg_match('/xmlns:(.+)/', $attrName, $matches)) {
					$this->figNamespace = $matches[1] . ':';
					break;
				}
			}
		}

		$pos = xml_get_current_byte_index($xmlParser);
		$lineNumber = xml_get_current_line_number($xmlParser);

		if($this->parentViewElement) {
			$view = &$this->parentViewElement->view;
		}
		else {
			$view = &$this;
		}

		if($tagName == $this->figNamespace . TagFigAttr::TAGNAME) {
			$newElement = new TagFigAttr($view, $tagName, $lineNumber);
		}
		else {
			$newElement = new ViewElementTag($view, $tagName, $lineNumber);
		}
		$newElement->setCurrentFile($this->file);
		$newElement->setAttributes($attributes);


		if( ($this->rootNode === null) && $this->parentViewElement )
		{
			$this->rootNode = &$this->parentViewElement;
			$this->stack[] = &$this->parentViewElement;
		}

		if($this->rootNode) {
			$parentElement = & $this->stack[count($this->stack)-1];
			$newElement->parent = &$parentElement;

			//Since the new element has a parent, then the parent is not autoclose.
			$newElement->parent->autoclose = false;

			$parentElement->appendChild($newElement);
		}
		else
		{
			//If there no root node yet, then this new element is actually
			//the root element for the view.
			$this->rootNode = &$newElement;
		}

		$this->stack[] = & $newElement;
	}
	public function closeTagHandler($xmlParser, $tagName) {
		$element = & $this->stack[count($this->stack) - 1];
		array_pop($this->stack);

		//$element->autoclose is set to true at creation of the tag,
		//and then is invalidated as the tag gets inner contents.
		//If the flag is still true here, it means that it has no inner contents.
		//So we might have a chance to find the /> sequence right before current byte position.
		if( $element->autoclose ) {
			$pos = xml_get_current_byte_index($xmlParser);
			//The /> sequence as the previous 2 chars of current position
			//works on Windows XP Pro 32bits with libxml 2.6.26.
			if(substr($this->source, $pos - 2, 2) == '/>') {
				return;
			}
			//Find the opening bracket < of the closing tag:
			$latestOpeningBracket = strrpos(substr($this->source, 0, $pos + $this->firstTagOffset+1), '<');
			//Very risky. Work with libxml 2.6.26 on Windows XP Pro 32bit.
			//Unsure for any other platform...
			//TODO: Anyway it works only for top-level fig file. Not for included files, it seems.
			if(!preg_match('#^<[^>]+/>#', substr($this->source, $latestOpeningBracket))) {
				$element->autoclose = false;
			}
		}
		//TODO: Bad bad! hard-coded br tag. Just out of laziness. The only real solution will be to rewrite an XML parser (on-the-fly in the file).
		if($tagName == 'br')
			$element->autoclose = true;
	}

	/**
	 * XML parser handler for CDATA
	 *
	 * @access private
	 * @param XML_resource $xmlParser
	 * @param string $cdata
	 */
	function cdataHandler($xmlParser, $cdata) {
		//Last element in stack = parent element of the CDATA.
		$currentElement = &$this->stack[count($this->stack)-1];
		$currentElement->appendCDataChild($cdata);
	}


	function errorMessage($errorMessage) {
		$lineNumber = xml_get_current_line_number($this->xmlParser);
		$filename = ($this->file) ? $this->file->getFilename() : '(null)';
		$this->logger->error("$filename($lineNumber): $errorMessage");
	}

	/**
	 * Inject the content of the fig:plug nodes
	 * into the corresponding slots.
	 *
	 * @param string $input
	 * @return string
	 */
	private function plugIntoSlots($input) {
		if(count($this->slots) == 0)
			return $input;

		$result = $input;

		/**
		 * @var Slot
		 */
		$slot = null;
		foreach($this->slots as $slotName => $slot) {
			$plugOutput = '';
			$slotPos = strpos($result, $slot->getAnchorString());

			if( isset($this->plugs[$slotName]) ) {
				foreach ($this->plugs[$slotName] as $plugElement) {
					$plugElement->clearAttribute($this->figNamespace . 'plug');

					$plugRender = $plugElement->render();
					if($plugRender === false) {
						return false;
					}

					if($plugElement->hasAttribute($this->figNamespace . 'append')) {
						if($plugElement->evaluate($plugElement->getAttribute($this->figNamespace . 'append'))) {
							$plugOutput .= $plugRender;
						}
						else {
							$plugOutput = $plugRender;
						}
					}
					else {
						$plugOutput = $plugRender;
					}

					$plugElement->setAttribute($this->figNamespace . 'plug', $slotName);
				}
				$result = substr_replace( $result, $plugOutput, $slotPos, strlen($slot->getAnchorString()) + $slot->getLength() );
			}
			else {
			  // If a slot did not receive any plugged content, we use its
			  // hardcoded template content as default. But we still need
			  // to clear the placeholder that was used during slots/plugs reconciliation!
				$result = substr_replace( $result, $plugOutput, $slotPos, strlen($slot->getAnchorString()) );
			}
			$slot->setLength(strlen($plugOutput));

		}
		return $result;
	}

	public function pushStackData($data) {
		array_push($this->callStackData, $data);
	}
	public function popStackData() {
		array_pop($this->callStackData);
	}
	public function fetchData($name) {
		if($name == '/') {
			return $this->callStackData[0];
		}
		if($name == '.') {
			//TODO: Attention ce n'est pas le dernier de la pile,
			//qu'il faut prendre, mais le contexte actuel !
			//(gare aux boucles imbriqu�es).
			return $this->callStackData[count($this->callStackData) - 1];
		}
		if($name == '..') {
			//If there is no parent to the current context,
			//stop searching.
			if(count($this->callStackData) - 2 < 0) {
				return null;
			}
			//TODO: Attention ce n'est pas l'avant-dernier de la pile,
			//qu'il faut prendre, mais le vrai parent du contexte actuel !
			//(gare aux boucles imbriqu�es)
			return $this->callStackData[count($this->callStackData) - 2];
		}

		$stackDepth = count($this->callStackData);
		for($i = $stackDepth - 1; $i >= 0; --$i) {

			//If the piece of data is actually an object, rather than an array,
			//then try to apply a Get method on the name of the property (� la Java Bean).
			//If the object does not expose such method, try to obtain the object's property directly.
			if(is_object($this->callStackData[$i])) {
				$getter = 'get' . strtoupper($name[0]) . substr($name, 1);
				if(method_exists($this->callStackData[$i], $getter))
					return $this->callStackData[$i]->$getter();
				else {
					$objectVars = get_object_vars($this->callStackData[$i]);
					if(array_key_exists($name, $objectVars)) {
						return $objectVars[$name];
					}
				}
			}

			else {
				if(is_array($this->callStackData[$i]) && array_key_exists($name, $this->callStackData[$i]))
					return $this->callStackData[$i][$name];
			}

		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getFilename() {
		return $this->file->getFilename();
	}
	/**
	 * @return string
	 */
	public function getTranslationPath() {
		return $this->translationPath;
	}
	/**
	 * @param string $path
	 */
	public function setTranslationPath($path) {
		$this->translationPath = $path;
	}

	/**
	 * Called by the ViewElementTag::fig_feed method,
	 * to instanciate a feed by its class name.
	 *
	 * @param string $classname
	 * @param array $attributes associative array of the extended parameters
	 * @return Feed null if no factory handles specified class.
	 */
	public function createFeed($classname, array $attributes) {
		if(in_array($classname, $this->feedFactoryForClass)) {
			$feedFactory = $this->feedFactoryForClass[$classname];
			return $feedFactory->create($classname, $attributes);
		}
		else {
			foreach ($this->feedFactories as $factory) {
				if(null != ($feedInstance = $factory->create($classname, $attributes))) {
					$this->feedFactoryForClass[$classname] = $factory;
					return $feedInstance;
				}
			}
			return null;
		}
	}

	/**
	 * Checks whether specified attribute name is in the fig namespace
	 * (whose prefix can be overriden by xmlns declaration).
	 * @param string $attribute
	 * @return boolean
	 */
	public function isFigAttribute($attribute) {
		return (substr($attribute, 0, strlen($this->figNamespace)) == $this->figNamespace);
	}

	/**
	 * This method is called by ViewElementTag objects, when processing
	 * a fig:slot item.
	 * @param string $slotName
	 * @param Slot $slot
	 */
  public function assignSlot($slotName, Slot & $slot) {
      $this->slots[$slotName] = & $slot;
  }

  public function addPlug($slotName, ViewElementTag & $element) {
    $this->plugs[$slotName] [] = & $element;
  }
}
