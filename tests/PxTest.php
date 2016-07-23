<?php

namespace Z38\PcAxis\Tests;

use Z38\PcAxis\Px;

class PxTest extends \PHPUnit_Framework_TestCase
{
    public function testCodepage()
    {
        $px = new Px(__DIR__.'/samples/07A01_02.px');

        $title = $px->keyword('TITLE')->values[0];
        $this->assertSame('Загальна площа житлового фонду (тис. кв.м) - Територія і Рік', $title);
    }

    public function testVariables()
    {
        $px = new Px(__DIR__.'/samples/CNA12-20130121.px');

        $this->assertSame(
            ['County', 'Year', 'Sex', 'Usual Residence One Year Previous'],
            $px->variables()
        );
    }

    public function testVariablesWithoutStub()
    {
        $px = new Px(__DIR__.'/samples/020_ati_tau_102_en.px');

        $this->assertSame(
            ['Year', 'Quarter'],
            $px->variables()
        );
    }

    public function testValues()
    {
        $px = new Px(__DIR__.'/samples/CNA12-20130121.px');

        $this->assertSame(
            ['1971', '1981', '1986', '1991', '1996', '2002', '2006', '2011'],
            $px->values('Year')
        );
    }

    public function testCodes()
    {
        $px = new Px(__DIR__.'/samples/CNA12-20130121.px');

        $this->assertSame(
            ['0', '01', '02', '021', '022', '023', '024'],
            $px->codes('Usual Residence One Year Previous')
        );
    }

    public function testCodesNone()
    {
        $px = new Px(__DIR__.'/samples/CNA12-20130121.px');

        $this->assertNull($px->codes('Year'));
    }

    public function testDatum()
    {
        $px = new Px(__DIR__.'/samples/CNA12-20130121.px');

        $this->assertSame('2893172', $px->datum([0, 0, 0, 0]));
        $this->assertSame('3213', $px->datum([20, 3, 1, 3]));
        $this->assertSame('265', $px->datum([40, 7, 2, 6]));
    }

    public function testMissing()
    {
        $px = new Px(__DIR__.'/samples/CNA12-20130121.px');

        $this->assertSame('..', $px->datum([23, 7, 0, 3]));
    }

    public function testHasKeyword()
    {
        $px = new Px(__DIR__.'/samples/CNA12-20130121.px');

        $this->assertTrue($px->hasKeyword('SUBJECT-CODE'));
        $this->assertFalse($px->hasKeyword('INVALID-KEYWORD'));
    }
}
