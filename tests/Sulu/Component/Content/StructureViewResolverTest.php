<?php

namespace Sulu\Component\Content;

use Sulu\Component\Content\StructureViewResolver;
use Prophecy\PhpUnit\ProphecyTestCase;

class StructureViewResolverTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->contentTypeManager = $this->prophesize('Sulu\Component\Content\ContentTypeManagerInterface');
        $this->contentType = $this->prophesize('Sulu\Component\Content\ContentTypeInterface');
        $this->structure = $this->prophesize('Sulu\Component\Content\StructureInterface');

        $this->resolver = new StructureViewResolver($this->contentTypeManager->reveal());
    }

    protected function getProperty($name, $contentTypeName)
    {
        $p = $this->prophesize('Sulu\Component\Content\PropertyInterface');
        $p->getName()->willReturn($name);
        $p->getContentTypeName()->willReturn($contentTypeName);

        return $p;
    }

    public function provideGetViewData()
    {
        return array(
            array(
                array(
                    array('prop_1'),
                ),
                array(
                    'prop_1' => array(
                        'some' => 'data'
                    ),
                ),
            ),
            array(
                array(
                ),
                array(
                ),
            )
        );
    }

    /**
     * @dataProvider provideGetViewData
     */
    public function testGetViewData($props, $expected)
    {
        $properties = array();
        foreach ($props as $prop) {
            $property = $this->getProperty($prop[0], 'type_1');
            $this->contentType->getViewData($property)->willReturn(array(
                'some' => 'data'
            ));

            $properties[] = $property->reveal();
        }
        $this->contentTypeManager->get('type_1')->willReturn($this->contentType);

        $this->structure->getProperties()->willReturn($properties);

        $res = $this->resolver->resolve($this->structure->reveal());

        $this->assertEquals($expected, $res);
    }
}
