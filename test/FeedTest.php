<?php

class FeedTest extends PHPUnit_Framework_TestCase
{
  public function testGetParemeterIntWithDefaultValue()
  {
    $view = new \figdice\View();
    $view->loadString(
      '<fig:template>' .
        '<fig:feed class="\ParamTest1Feed" target="data" />' .
        '<fig:mute fig:text="/data"/>' .
    '</fig:template>');
    $this->assertEquals(12, $view->render());
  }

  public function testGetParemeterBool()
  {
    $view = new \figdice\View();
    $view->loadString(
      '<fig:template>' .
        '<fig:feed class="\ParamTest2Feed" param1="true" target="data" />' .
        '<fig:mute fig:text="/data"/>' .
    '</fig:template>');
    $this->assertEquals(1, $view->render());
  }

  public function testGetParemeterString()
  {
    $view = new \figdice\View();
    $view->loadString(
      '<fig:template>' .
        '<fig:feed class="\ParamTest3Feed" param1="\'a\'" target="data" />' .
        '<fig:mute fig:text="/data"/>' .
    '</fig:template>');
    $this->assertEquals('ab', $view->render());
  }
}

class ParamTest1Feed extends \figdice\Feed
{
  public function run()
  {
    return $this->getParameterInt('not-there', 12);
  }
}
class ParamTest2Feed extends \figdice\Feed
{
  public function run()
  {
    return $this->getParameterBool('param1') & $this->getParameterBool('not-there', true);
  }
}
class ParamTest3Feed extends \figdice\Feed
{
  public function run()
  {
    return $this->getParameterString('param1') . $this->getParameterString('not-there', 'b');
  }
}