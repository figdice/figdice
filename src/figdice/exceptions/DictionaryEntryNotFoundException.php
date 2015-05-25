<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
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

class DictionaryEntryNotFoundException extends \Exception {

  private $key;
  private $dictName;
  private $fileName;
  private $lineNumber;

  private static function makeExceptionMessage($key, $dictName = null, $fileName = null, $lineNumber = 0)
  {
    $msg = 'Dictionary Key Not Found: "' . $key . '"';
    if ($dictName) {
      $msg .= ' in dic "' . $dictName . '"';
    }
    if ($fileName) {
      $msg .= ' in template "' . $fileName . '('.$lineNumber.')"';
    }
    return $msg;
  }
  public function __construct($key)
  {
    parent::__construct(self::makeExceptionMessage($key));
    $this->key = $key;
  }

  public function getKey()
  {
    return $this->key;
  }
  public function getDictionaryName()
  {
    return $this->dictName;
  }
  public function setDictionaryName($dictName)
  {
    $this->dictName = $dictName;
    $this->message = self::makeExceptionMessage($this->key, $this->dictName, $this->fileName, $this->lineNumber);
  }
  public function setTemplateFile($fileName, $xmlLineNumber)
  {
    $this->fileName = $fileName;
    $this->lineNumber = $xmlLineNumber;
    $this->message = self::makeExceptionMessage($this->key, $this->dictName, $this->fileName, $this->lineNumber);
  }
}
