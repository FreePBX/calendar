<?php

namespace Eluceo\iCal\Property;

class ArrayValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider arrayValuesProvider
     */
    public function testArrayValue($values, $expectedOutput)
    {
        $arrayValue = new ArrayValue($values);

        $this->assertEquals($expectedOutput, $arrayValue->getEscapedValue());
    }

    public function arrayValuesProvider()
    {
        return [[[], ''], [['Lorem'], 'Lorem'], [['Lorem', 'Ipsum'], 'Lorem,Ipsum'], [['Lorem', '"doublequotes"'], 'Lorem,\"doublequotes\"']];
    }
}
