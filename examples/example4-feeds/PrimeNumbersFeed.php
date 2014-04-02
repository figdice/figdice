<?php

use figdice\Feed;

/**
 * A Feed is a subclass of FigDice's Feed abstract parent.
 * It implements a run() method, invoked by the engine at
 * run-time, whenever the template needs to obtain from your
 * feed class the data it is able to supply.
 */
class PrimeNumbersFeeds extends Feed
{
	public function __construct() {
		parent::__construct();
	}

	/**
	 * In our example, we will simply return an array of integers.
	 * Of course, you can return any mixed result including associative
	 * arrays (of any depth), or getter-equipped objects, just as in
	 * the view's mount() method.
	 * @see \figdice\Feed::run()
	 */
	public function run() {
		return array(1, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41);
	}
}
