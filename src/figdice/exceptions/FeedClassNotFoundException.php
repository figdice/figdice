<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2016, Gabriel Zerbib.
 * @version 2.3.2
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\exceptions;

use Exception;
/**
 * This exception is raised by the engine when you import a fig:feed
 * of a class which the program cannot locate.
 * Unlike normal runtime situations such as an argument of invalid type in a
 * Function call, or a missing symbol in the universe, a Class Not Found
 * is an indication that your program layout is ill-designed (the problem
 * does not depend on a particular dataset). This exception causes the
 * rendering workflow to stop.
 * You should not encounter this situation in production.
 */
class FeedClassNotFoundException extends Exception {
	private $classname;

    /**
     * FeedClassNotFoundException constructor.
     * @param string $classname
     * @param string $filename
     * @param int $line
     */
	public function __construct($classname, $filename, $line) {
		parent::__construct('Could not find factory for feed: ' . $classname);
		$this->classname = $classname;
		$this->file = $filename ?? '';
		$this->line = $line;
	}

    /**
     * @param string $filename
     * @return $this
     */
    public function setFile($filename)
    {
        $this->file = $filename;
        return $this;
    }
}
