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

namespace figdice;

use figdice\classes\AutoloadFeedFactory;
use figdice\classes\Context;
use figdice\classes\MagicReflector;
use figdice\classes\NativeFunctionFactory;
use figdice\classes\TagFigAttr;
use figdice\classes\TagFigCdata;
use figdice\classes\TagFigDictionary;
use figdice\classes\TagFigFeed;
use figdice\classes\TagFigInclude;
use figdice\classes\TagFigMount;
use figdice\classes\ViewElementTag;
use figdice\exceptions\FeedClassNotFoundException;
use figdice\exceptions\FeedClassNotFoundRenderingException;
use figdice\exceptions\FileNotFoundException;
use figdice\exceptions\RequiredAttributeException;
use figdice\exceptions\RequiredAttributeParsingException;
use figdice\exceptions\TagRenderingException;
use figdice\exceptions\XMLParsingException;

use figdice\exceptions\RenderingException;
use figdice\classes\XMLEntityTransformer;

/**
 * Main component of the FigDice library.
 * The View object represents the transform lifecycle from a template into a rendered document.
 *
 * After creating a View instance, you may register one or more {@link FeedFactory} objects (see {@see registerFeedFactory}),
 * and you may register one or more {@link FunctionFactory} object (see {@see registerFunctionFactory}) and one {@see FilterFactory} (see {@see setFilterFactory}).
 *
 * Then you would {@see loadFile} an XML source file, and finally {@see render} it to obtain its final result (which you would typically output to the browser).
 */
class View implements \Serializable {

	const GLOBAL_PLUGS = 'GLOBAL_PLUGS';

	/**
	 * Textual source of the transformation.
	 *
	 * @var string
	 */
	private $source;

	/**
	 * The source filename.
	 * @var string
	 */
	private $filename;

	/**
	 * The directory where to store temporary files (results of compilation).
	 * @var string
	 */
	private $tempPath;
	/**
	 * The Filter Factory instance.
     * If no factory is defined, the View will consider that the filter class are already
     * (or auto-) loaded, or PHP will raise the usual class not found exception.
     * Also, without a factory, the filter instance is constructed with no arguments.
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
	 * @var ViewElementTag
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
	 * @var resource
	 */
	private $xmlParser;


	/**
	 * Associative array of the named macros.
	 * A Macro is a ViewElementTag that is rendered
	 * only upon calling from within another element.
	 *
	 * @var ViewElementTag[]
	 */
	public $macros;


	/**
	 * Used internally at parse-time to determine whether when the very
	 * first tag opening occurs.
	 * @var boolean
	 */
	private $firstOpening;

	/**
	 * Cache of Lexers.
	 * @var array of Lexer
	 */
	public $lexers;

	/**
	 * The FeedFactory objects passed by the caller of the view,
	 * which are used to instanciate the feeds.
	 * @var FeedFactory[]
	 */
	public $feedFactories = array();

	/**
	 * Cache mechanism for feed instanciation.
	 * @var FeedFactory[] classname => factory object
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

	private $options = [];

	/**
	 * View constructor.
	 * @param array $options Optional indexed array of options, for specific behavior of the library.
	 * Introduced in 2.3 for the remodeling of the plug execution context.
	 */
	public function __construct(array $options = []) {
		$this->options = $options;
		$this->source = '';
		$this->rootNode = null;
		$this->stack = array();
		$this->lexers = array();
		$this->callStackData = array(array());
		$this->functionFactories = array(new NativeFunctionFactory());
		$this->language = null;
		$this->filename = null;
	}

	/**
	 * Checks whether the View has the specified option configured at construction.
	 * @param $option
	 * @return bool
	 */
	public function hasOption($option)
	{
		return in_array($option, $this->options);
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
	public function loadFile($filename) {
		$this->filename = $filename;

		if(file_exists($filename)) {
			$this->source = file_get_contents($filename);
		}
		else {
			$message = "File not found: $filename";
			throw new FileNotFoundException($message, $filename);
		}
	}

	/**
	 * Instead of loading a file, you can load a string, and optionally pass
	 * a "working directory" (mainly useful for includes).
	 * This creates a File object with no real filesystem location.
	 * @param string $string
	 * @param string|null $workingDirectory
	 */
	public function loadString($string, $workingDirectory = null) {
	  $this->filename = $workingDirectory . '/(null)';
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
     * @throws RequiredAttributeException
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

		try {
            $bSuccess = xml_parse($this->xmlParser, $this->source);
        } catch (RequiredAttributeParsingException $ex) {
		    throw new RequiredAttributeException($ex->getTag(), $this->filename, $ex->getLine(), $ex->getMessage(), $ex);
        }

        if ($bSuccess) {
            $errMsg = '';
            $lineNumber = 0;
        }
		else {
			$errMsg = xml_error_string(xml_get_error_code($this->xmlParser));
			$lineNumber = xml_get_current_line_number($this->xmlParser);
			if(count($this->stack)) {
				$lastElement = $this->stack[count($this->stack) - 1];
				if($lastElement instanceof ViewElementTag) {
					$errMsg .= '. Last element: ' . $lastElement->getTagName();
				}
			}
		}

		xml_parser_free($this->xmlParser);
		$this->bParsed = $bSuccess;

		if(! $bSuccess ) {
			throw new XMLParsingException(
					$errMsg,
					($this->filename ? $this->filename : '(null)'),
					$lineNumber);
		}
	}

    /**
     * Process parsed source and render view,
     * using the data universe.
     * @return string
     * @throws FeedClassNotFoundException
     * @throws RenderingException
     * @throws RequiredAttributeException
     * @throws XMLParsingException
     */
	public function render() {
		if(! $this->bParsed) {
			$this->parse();
		}

		if (! $this->rootNode) {
			throw new XMLParsingException('No template file loaded', 0);
		}

        $context = new Context($this);

		// DOCTYPE
        // The doctype is necessarily on the root tag, declared as an attribute, example:
        //   fig:doctype="html"
        // However, it can be on the root node of an included template (when using the reverse plug/slot pattern)
        $context->setDoctype($this->rootNode->getAttribute($this->figNamespace . 'doctype'));

        try {
            $result = $this->rootNode->render($context);
        } catch (RequiredAttributeParsingException $ex) {
            throw new RequiredAttributeException($ex->getTag(), $context->getFilename(), $ex->getLine(), $ex->getMessage(), $ex);
        } catch (TagRenderingException $ex) {
            throw new RenderingException($ex->getTag(), $context->getFilename(), $ex->getLine(), $ex->getMessage(), $ex);
        } catch (FeedClassNotFoundRenderingException $ex) {
            throw new FeedClassNotFoundException($ex->getClassname(), $context->getFilename(), $ex->getLine(), $ex);
        }


		$result = $this->plugIntoSlots($context, $result);

		// Take care of the doctype at top of output
		if ($context->getDoctype()) {
			$result = '<!doctype ' . $context->getDoctype() . '>' . "\n" . $result;
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
	public function setFilterFactory(FilterFactory $filterFactory) {
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
	public function mount($mountingName, $data) {
		$this->callStackData[0][$mountingName] = $data;
	}

    /**
     * Returns an assoc array of the universe data.
     * During view rendering, the unvierse is made of layers, along the iterations and macro calls etc.
     * One same key can exist in a lower layer and in another layer above it,
     * in which case the upper version is used in priority.
     * This method merges top-bottom (the upper key overwrites the lower one).
     * @return mixed
     */
	public function getMergedData()
    {
        $result = [];
        foreach ($this->callStackData as $layer) {
            $result = array_merge($layer, $result);
        }
        return $result;
    }


	private function openTagHandler($xmlParser, $tagName, $attributes) {
		if($this->firstOpening) {
			$this->firstOpening = false;

			//Position the namespace:
			$matches = null;
			foreach($attributes as $attrName => $attrValue) {
				if(preg_match('/figdice/', $attrValue) &&
					 preg_match('/xmlns:(.+)/', $attrName, $matches)) {
					$this->figNamespace = $matches[1] . ':';
					break;
				}
			}

            // Remove the fig xmlns directive from the list of attributes of the opening root tag
            // (as it should not be rendered)
            unset($attributes['xmlns:' . substr($this->figNamespace, 0, strlen($this->figNamespace) - 1)]);
        }

		$lineNumber = xml_get_current_line_number($xmlParser);


		//
		// Detect special tags
        //
		if($tagName == $this->figNamespace . TagFigAttr::TAGNAME) {
			$newElement = new TagFigAttr($tagName, $lineNumber);
		}
		else if ($tagName == $this->figNamespace . TagFigFeed::TAGNAME) {
		    $newElement = new TagFigFeed($tagName, $lineNumber);
        }
		else if ($tagName == $this->figNamespace . TagFigInclude::TAGNAME) {
		    $newElement = new TagFigInclude($tagName, $lineNumber);
        }
		else if ($tagName == $this->figNamespace . TagFigMount::TAGNAME) {
		    $newElement = new TagFigMount($tagName, $lineNumber);
        }
		else if ($tagName == $this->figNamespace . TagFigCdata::TAGNAME) {
		    $newElement = new TagFigCdata($tagName, $lineNumber);
        }
		else if ($tagName == $this->figNamespace . TagFigDictionary::TAGNAME) {
		    $newElement = new TagFigDictionary($tagName, $lineNumber);
        }


		else {
			$newElement = new ViewElementTag($tagName, $lineNumber);
		}

		$newElement->setAttributes($this->figNamespace, $attributes);


		if($this->rootNode) {
            /** @var ViewElementTag $parentElement */
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
			$latestOpeningBracket = strrpos(substr($this->source, 0, $pos + 1), '<');
			if(!preg_match('#^<[^>]+/>#', substr($this->source, $latestOpeningBracket))) {
				$element->autoclose = false;
			}
		}
	}


    /**
	 * XML parser handler for CDATA
	 *
	 * @param resource $xmlParser
	 * @param string $cdata
	 */
	private function cdataHandler($xmlParser, $cdata) {
		//Last element in stack = parent element of the CDATA.
		$currentElement = &$this->stack[count($this->stack)-1];
		$currentElement->appendCDataChild($cdata);
	}


    /**
     * Inject the content of the fig:plug nodes
     * into the corresponding slots.
     *
     * @param Context $context
     * @param string $input
     * @return string
     */
	private function plugIntoSlots(Context $context, $input) {
	    $slots = $context->getSlots();
		if(count($slots) == 0) {
			// Nothing to do.
			return $input;
		}

		$result = $input;

		foreach($slots as $slotName => $slot) {
			$plugOutput = '';
			$slotPos = strpos($result, $slot->getAnchorString());

            $plugsForSlot = $context->getPlugs($slotName);

			if( null != $plugsForSlot ) {

				foreach ($plugsForSlot as $plug) {

					if ($this->hasOption(self::GLOBAL_PLUGS)) {
						$plugElement = $plug->getTag();
						$plugElement->clearAttribute($this->figNamespace . 'plug');

						$plugRender = $plugElement->render($context);

						if ($plugElement->evalFigAttribute($context, 'append')) {
							$plugOutput .= $plugRender;
						}
						else {
							$plugOutput = $plugRender;
						}

						$plugElement->setAttribute($this->figNamespace . 'plug', $slotName);
					}

					else {
						$plugRender = $plug->getRenderedString();
						if ($plug->isAppend()) {
							$plugOutput .= $plugRender;
						}
						else {
							$plugOutput = $plugRender;
						}
					}


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

    /**
     * @internal
     * @param $data
     */
	public function pushStackData($data) {
		array_push($this->callStackData, $data);
	}

    /**
     * @internal
     */
    public function popStackData() {
		array_pop($this->callStackData);
	}
	public function fetchData($name) {
		if($name == '/') {
			return $this->callStackData[0];
			
		}
		if($name == '.') {
			return $this->callStackData[count($this->callStackData) - 1];
		}
		if($name == '..') {
			//If there is no parent to the current context,
			//stop searching.
			if(count($this->callStackData) - 2 < 0) {
				return null;
			}

			return $this->callStackData[count($this->callStackData) - 2];
		}

		$stackDepth = count($this->callStackData);
		for($i = $stackDepth - 1; $i >= 0; --$i) {

			//If the piece of data is actually an object, rather than an array,
			//then try to apply a Get method on the name of the property (a la Java Bean).
			//If the object does not expose such method, try to obtain the object's property directly.
			if(is_object($this->callStackData[$i])) {
				$getter = 'get' . ucfirst($name);
				if(method_exists($this->callStackData[$i], $getter))
					return $this->callStackData[$i]->$getter();
				else {
					$objectVars = get_object_vars($this->callStackData[$i]);
					if(array_key_exists($name, $objectVars)) {
						return $objectVars[$name];
					}
					else {
                        return MagicReflector::invoke($this->callStackData[$i], $getter);
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
		return $this->filename;
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
	 * to instantiate a feed by its class name.
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
			// If no factory is registered,
      // let's use at least the Autoload factory
      if (0 == count($this->feedFactories)) {
        $this->registerFeedFactory(new AutoloadFeedFactory());
      }

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


  public function serialize()
  {
      if (! $this->bParsed) {
          $this->parse();
      }

      return serialize([
          'f' => $this->getFilename(),
          'ns' => $this->figNamespace,
          'root' => $this->rootNode
      ]);
  }
  public function unserialize($serialized)
  {
      $data = unserialize($serialized);

      $this->bParsed = true;
      $this->filename = $data['f'];
      $this->figNamespace = $data['ns'];
      $this->rootNode = $data['root'];
  }
}
