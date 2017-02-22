<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2016, Gabriel Zerbib.
 * @version 2.3.3
 * @license GPLv3
 * @package FigDice
 *
 * This file is part of FigDice.
 */

use figdice\View;

class FunctionXmlTest extends PHPUnit_Framework_TestCase
{
  /**
   * @expectedException \figdice\exceptions\XMLParsingException
   */
  public function testFunctionXmlForInvalidXmlIssuesException()
  {
    // Deliberately supplying invalid XML island,
    // but because we're inside a Fig template which must be valid XML,
    // we use CDATA to provide a string which will be considered as xml by DOM lib.
    $xml = <<<TEMPLATE
<fig:template>
  <fig:mount target="myXml"><![CDATA[
    <node1>value1
    <node2>value2</node2>
  ]]></fig:mount>
  <fig:mute fig:text="xpath(xml(/myXml), '/xml/node1')"/>
</fig:template>
TEMPLATE;

    $view = new View();
    $view->loadString($xml);
    $view->render();

    $this->assertTrue(false, 'Should never occur: an exception was caught before.');
  }

  public function testFunctionXmlWithoutRootNodeDefaultsToXmlRootNode()
  {
    $xml = <<<TEMPLATE
<fig:template>
  <fig:mount target="myXml">
    <node1>value1</node1>
    <node2>value2</node2>
  </fig:mount>
  <fig:mute fig:text="xpath(xml(/myXml), '/xml/node1')"/>
</fig:template>
TEMPLATE;

    $view = new View();
    $view->loadString($xml);
    $output = trim($view->render());

    $this->assertEquals('value1', $output);
  }

  public function testFunctionXmlWithoutRootNodeWithExplicitDefaultRoot()
  {
    $xml = <<<TEMPLATE
<fig:template>
  <fig:mount target="myXml">
    <node1>value1</node1>
    <node2>value2</node2>
  </fig:mount>
  <fig:mute fig:text="xpath(xml(/myXml, 'explicit'), '/explicit/node1')"/>
</fig:template>
TEMPLATE;

    $view = new View();
    $view->loadString($xml);
    $output = trim($view->render());

    $this->assertEquals('value1', $output);
  }

  public function testFunctionXmlWithRootUsesSpecified()
  {
    $xml = <<<TEMPLATE
<fig:template>
  <fig:mount target="myXml">
    <myroot>
      <node1>value1</node1>
      <node2>value2</node2>
    </myroot>
  </fig:mount>
  <fig:mute fig:text="xpath(xml(/myXml), '/myroot/node1')"/>
</fig:template>
TEMPLATE;

    $view = new View();
    $view->loadString($xml);
    $output = trim($view->render());

    $this->assertEquals('value1', $output);
  }
}
