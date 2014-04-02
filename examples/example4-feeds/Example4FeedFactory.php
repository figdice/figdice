<?php
use figdice\FeedFactory;

/**
 * Thanks to the Factory pattern, your program can
 * pass anything to the constructor: your Factory is
 * responsible for keeping this contextual data, and pass
 * it in turn to the subsequently instantiated feeds if needed.
 * This way, the Feed instances can receive important context 
 * information such as DB connection etc., whereas the Template XML
 * itself has no idea about them.
 */
class Example4FeedFactory implements FeedFactory
{
	/**
	 * In this simple example, we did not declare additional
	 * arguments for our factory. You can experiment by yourself.
	 */
	public function __construct() {
	}
	
	/**
	 * In this simple example, we did not pass attributes to
	 * the fig:feed invokation.
	 * Had we specify XML attributes (in addition to class and target),
	 * they would be interpreted by the Expression engine, and passed
	 * as the $attribute argument, as key/value pairs. 
	 * @see \figdice\FeedFactory::create()
	 */
	public function create($className, array $attributes) {
		
		/*
		 * Your Feed Factory can handle any number of Feeds.
		 * It is advised to group the Feed classes of similar functional
		 * or technical role, into one same Factory.
		 * For example, you could make a DatabaseAwareFeedFactory,
		 * or a SocialNetworksFeedFactory, and so on.
		 */
		
		if ($className == 'PrimeNumbersFeed') {
			require_once 'PrimeNumbersFeed.php';
			return new PrimeNumbersFeeds();
		}
	} 
}
