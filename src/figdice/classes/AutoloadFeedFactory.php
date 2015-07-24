<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.1
 * @licenses GPL-v3
 * @package FigDice
 *
 * This file is part of FigDice @see http://figdice.org
 */

namespace figdice\classes;


use figdice\FeedFactory;

class AutoloadFeedFactory implements FeedFactory
{
  public function create($className, array $attributes)
  {
    // Thanks to autoload, any feed can simply be invoked by
    // its full namespace+class, and then it will be automagically
    // loaded here.
    return new $className;
  }
}
