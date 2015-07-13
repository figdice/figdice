<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
 * @package FigDice
 * @licenses GPLv3 see <http://www.gnu.org/licenses/>
 *
 * This file is part of FigDice.
 */
namespace figdice\classes;


class MockNullLogger
{
  public function __call ($function, $args)
  {
    //noop
  }
}
