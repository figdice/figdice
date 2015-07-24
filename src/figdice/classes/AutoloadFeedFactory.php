<?php

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
