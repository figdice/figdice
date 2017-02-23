<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
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

namespace figdice\exceptions;

use figdice\classes\File;

class DictionaryNotFoundException extends \Exception {
  private $dictionaryName;

    /**
     * DictionaryNotFoundException constructor.
     * @param string $dictionaryName
     * @param string $file
     * @param int $xmlLineNumber
     */
    public function __construct($dictionaryName, $file, $xmlLineNumber)
  {
    parent::__construct('Dictionary "' . $dictionaryName . '" not found in template: ' . $file . '('.$xmlLineNumber.')');
    $this->dictionaryName = $dictionaryName;
    $this->file = $file;
    $this->line = $xmlLineNumber;
  }

  public function getDictionaryName()
  {
    return $this->dictionaryName;
  }
}
