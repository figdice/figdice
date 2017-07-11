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
