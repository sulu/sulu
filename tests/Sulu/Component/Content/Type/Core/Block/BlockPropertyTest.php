<?php

namespace vendor\sulu\sulu\tests\Sulu\Component\Content\Block;

use Sulu\Component\Content\Block\BlockProperty;

class BlockPropertyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->property = new BlockProperty(
            'foobar',
            $metadata = array(),
            $defaultTypeName = 'default_type_name',
            $mandatory = false,
            $multilingual = false,
            $maxOccurs = 1,
            $minOccurs = 1,
            $params = array(),
            $tags = array(),
            $col = null
        );

        $this->type1 = $this->getMockBuilder('Sulu\Component\Content\Block\BlockPropertyType')->disableOriginalConstructor()->getMock();
        $this->type1->expects($this->any())->method('getName')->will($this->returnValue('type1'));
        $this->type2 = $this->getMockBuilder('Sulu\Component\Content\Block\BlockPropertyType')->disableOriginalConstructor()->getMock();
        $this->type2->expects($this->any())->method('getName')->will($this->returnValue('type2'));

        $this->property->addType($this->type1);
        $this->property->addType($this->type2);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The block type "foo" has not been registered. Known block types are: [type1, type2]
     */
    public function testGetTypeNotKnown()
    {
        $this->property->getType('foo');
    }

    public function testGetType()
    {
        $res = $this->property->getType('type1');
        $this->assertSame($this->type1, $res);
    }
}
