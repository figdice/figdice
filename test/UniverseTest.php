<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2019, Gabriel Zerbib.
 * @package FigDice
 *
 * This file is part of FigDice.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use figdice\View;

/**
 * Unit Test Class for basic View loading
 */
class UniverseTest extends TestCase {

    private function symbolize($expr, $universe)
    {
        // In order to evaluate an expression, as seen by the view,
        // we will have it mount its result into a universe symbol
        // which we will read after the rendering.
        $view = new View();
        $view->loadString('<fig:mount target="unittest" value="' . $expr . '"/>');
        // Mount the passed data into the view
        foreach ($universe as $key => $value) {
            $view->mount($key, $value);
        }
        // Render (and thus evaluate our expression given the passed data)
        $view->render();

        // Now, obtain the merged universe as of end of rendering,
        $finalUnvierse = $view->getMergedData();
        // and return the value of the symbol that contains our evaluated expression.
        return $finalUnvierse['unittest'];
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


    /**
     * In this test, we push into the universe an object whose property of interest is accessible via
     * the magic __call. This means that the Reflection will not see the getter.
     * The FigDice engine relies on the @ method PhpDoc tag at class level, in order to resolve the property.
     */
    public function testObjectWithMagicGetterIsSeen()
    {
        $expected = 12;
        $object = new MyMagicObject($expected);
        $this->assertEquals($expected, $object->getMagicProp());

        // In absolute
        $this->assertEquals($expected, $this->symbolize('/myobj/magicProp', ['myobj' => $object]));

        // In relative:
        $view = new View();
        $view->loadString('<fig:mute fig:walk="/array" fig:text="magicProp"/>');
        $view->mount('array', [$object]);

        $rendered = $view->render();
        $this->assertEquals($expected, $rendered);

    }
}

/**
 * This class is used in test @see testObjectWithMagicGetterIsSeen
 * It does not explicitly define the 'getMagicProp' method, but proposes it via the following tag:
 * @method int getMagicProp()
 * which is enough for FigDice to make the magic method visible in Expressions.
 */
class MyMagicObject
{
    public function __construct($value)
    {
        $this->prop = $value;
    }
    public function __call($name, $arguments)
    {
        if ($name == 'getMagicProp')
            return $this->prop;
        return null;
    }
}
