<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.3
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

namespace figdice\classes\functions;

use \figdice\FigFunction;
use \figdice\classes\ViewElementTag;

/**
 * Class Function_xml
 *
 * This class exposes the "xml( )" function to FigDice expressions.
 * This function parses a string as an XML document, and returns a DOMXPath instance
 * bound to this DOMDocument.
 * If the XML parsing fails, a warning is triggered.
 * The returned DOMXPath reference can be used in subsequent `evaluate` calls,
 * through the "xpath( )" Fig function.
 *
 * A second string argument can be passed, to be used if the string cannot evaluate to valid XML due
 * to the lack of a root node, in which case this 2nd arg is used as name of root tag.
 */
class Function_xml implements FigFunction {

	/**
	 * @param {@link ViewElement} $viewElement
	 * @param integer $arity
	 * @param array $arguments
	 */
	public function evaluate(ViewElementTag $viewElement, $arity, $arguments) {
		$xmlString = $arguments[0];
		$xml = new \DomDocument();
		$successParse = @ $xml->loadXML($xmlString, LIBXML_NOENT);
		if (! $successParse) {
			$explicitRoot = $arity >= 2 ? $arguments[1] : 'xml';
			$xmlString = '<'.$explicitRoot.'>' . $arguments[0] . '</'.$explicitRoot.'>';
			// This time we let a warning be fired in case of invalid xml.
			$xml->loadXML($xmlString, LIBXML_NOENT);
		}
		$xpath = new \DOMXPath($xml);
		return $xpath;
	}
}