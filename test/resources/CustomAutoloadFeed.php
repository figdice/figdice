<?php
namespace some\figdice\test\ns;


use figdice\Feed;

class CustomAutoloadFeed extends Feed
{
  public function run()
  {
    return ['value' => $this->getParameterInt('some-param')];
  }
}
