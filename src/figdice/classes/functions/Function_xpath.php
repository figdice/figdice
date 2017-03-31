<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
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

use DOMAttr;
use DOMNodeList;
use DOMText;
use figdice\classes\Context;
use figdice\FigFunction;
use figdice\classes\FigDOMNodeList;

class Function_xpath implements FigFunction {
	public function __construct() {
	}

    /**
     * @param Context $context
     * @param integer $arity
     * @param array $arguments
     * @return \DOMElement|FigDOMNodeList|mixed
     */
    public function evaluate(Context $context, $arity, $arguments) {
		$domxpath = $arguments[0];
		$query = $arguments[1];
		if($arity == 3) {
			$context = $arguments[2];
			$result = $domxpath->evaluate($query, $context);
		}
		else {
			$result = $domxpath->evaluate($query);
		}

		//Some special situations:

		if($result instanceof DOMNodeList) {
			//The empty nodelist yields to empty result.
			if($result->length == 0) {
				return null;
			}

			//One-size nodelist => let's return the one element
			//instead of the list:
			else if($result->length == 1) {
				$result = $result->item(0);
			}

			else {
				return new FigDOMNodeList($result);
			}
		}

		if($result instanceof DOMText) {
			return $result->wholeText;
		}
		else if($result instanceof DOMAttr) {
			return $result->value;
		}

		return $result;
	}
}