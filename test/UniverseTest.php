<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.1.1
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 * FigDice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * FigDice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FigDice.  If not, see <http://www.gnu.org/licenses/>.
 */

use figdice\View;

/**
 * Unit Test Class for basic View loading
 */
class UniverseTest extends PHPUnit_Framework_TestCase {

	private function symbolize($expr, $universe)
  {
    $view = new View();
    $view->loadString('<fig:text fig:text="' . $expr . '"/>');
    foreach ($universe as $key => $value) {
      $view->mount($key, $value);
    }
    return $view->render();
  }

  /**
   * This test must be done with a real View object, instead of a Mock,
   * because it activates the bubbling Universe resolution.
   * TODO: At some point we will need to factor out the Universe resolution,
   * and make it less adhering to the View code.
   */
	public function testRelativeOneLevelSymbol()
	{
    $this->assertEquals(47, $this->symbolize('dummy', ['dummy' => 47]));
	}

  public function testRelativeTwoLevelSymbol()
  {
    $this->assertEquals(48, $this->symbolize('dummy/test', ['dummy' => ['test' => 48]]));
  }

  public function testAbsoluteOneLevelSymbol()
  {
    $this->assertEquals(47, $this->symbolize('/dummy', ['dummy' => 47]));
  }

  public function testAbsoluteTwoLevelSymbol()
  {
    $this->assertEquals(48, $this->symbolize('/dummy/test', ['dummy' => ['test' => 48]]));
  }

  public function testExclamMarkNextToSymbol()
  {
    $this->assertFalse($this->symbolize('dummy!=47', ['dummy' => 47]));
  }
  public function testExclamMarkAfterSymbol()
  {
    $this->assertFalse($this->symbolize('dummy !=47', ['dummy' => 47]));
  }

  public function testRelativePathDisambiguation()
  {
    //Check that heading dot is understated.
    $view = new View();
    $view->loadString(trim(
      '<fig:template>' .
        '<fig:mute fig:text="data"/>:' .
        '<fig:mute fig:walk="lines" fig:text="data"/>:' .
        '<fig:mute fig:walk="lines" fig:text="./data"/>' .
      '</fig:template>'
    ));
    $view->mount('data', 12);
    $view->mount('lines', [
      ['data' => 13],
      ['data' => 14]
    ]);

    $this->assertEquals('12:1314:1314', $view->render());
  }

  public function testDotInsideFunctionAndTopLevelDotIsFullUniverse()
  {
    $this->assertEquals(2, $this->symbolize('count(.)', ['a', 'b']));
  }

}
