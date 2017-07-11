<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.2
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
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
  public function __construct($key, $filename = null, $lineNumber = null)
  {
    parent::__construct(self::makeExceptionMessage($key, null, $filename, $lineNumber));
    $this->key = $key;
    $this->fileName = $filename;
    $this->lineNumber = $lineNumber;
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
