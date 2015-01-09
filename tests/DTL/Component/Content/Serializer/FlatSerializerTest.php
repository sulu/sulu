<?php

namespace DTL\Component\Content\Serializer;

use Prophecy\PhpUnit\ProphecyTestCase;

class FlatSerializerTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->serializer = new FlatSerializer();
        $this->node = $this->prophesize('PHPCR\NodeInterface');
    }

    public function provideSerializer()
    {
        return array(
            array(
                array(
                    'some_number' => 1234,
                    'animals' => array(
                        'title' => 'Smart content',
                        'sort_method' => 'asc',
                    ),
                    'options' => array(
                        'numbers' => array(
                            'two', 'three'
                        ),
                        'foo' => array(
                            'bar' => 'baz',
                            'boo' => 'bog',
                        ),
                    ),
                ),
                array(
                    'cont:some_number' => 1234,
                    'cont:animals' . FlatSerializer::ARRAY_DELIM . 'title' => 'Smart content',
                    'cont:animals' . FlatSerializer::ARRAY_DELIM . 'sort_method' => 'asc',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'numbers' . FlatSerializer::ARRAY_DELIM . '0' => 'two',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'numbers' . FlatSerializer::ARRAY_DELIM . '1' => 'three',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'foo' . FlatSerializer::ARRAY_DELIM . 'bar' => 'baz',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'foo' . FlatSerializer::ARRAY_DELIM . 'boo' => 'bog',
                ),
            ),
        );
    }

    /**
     * @dataProvider provideSerializer
     */
    public function testSerialize($data, $expectedRes)
    {
        foreach ($expectedRes as $propName => $propValue) {
            $this->node->setProperty($propName, $propValue)->shouldBeCalled();
        }

        $this->serializer->serialize($data, $this->node->reveal());
    }

    /**
     * @dataProvider provideSerializer
     */
    public function testDeserialize($expectedRes, $data)
    {
        $props = array();
        foreach ($data as $propName => $propValue) {
            $prop = $this->prophesize('Sulu\Component\Content\PropertyInterface');
            $prop->getValue()->willReturn($propValue);
            $props[$propName] = $prop;
        }

        $this->node->getProperties(FlatSerializer::NS . ':*')->willReturn($props);

        $res = $this->serializer->deserialize($this->node->reveal());

        $this->assertSame($expectedRes, $res);
    }
}
