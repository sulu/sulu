<?php

namespace DTL\Component\Content\Structure;

use DTL\Component\Content\Structure\ItemTest;
use DTL\Component\Content\Structure\Structure;

class StructureTest extends ItemTest
{
    protected $structure;
    protected $prop1;
    protected $prop2;
    protected $prop3;
    protected $prop4;
    protected $section;

    public function setUp()
    {
        $this->prop1 = new Property('prop_1_localized');
        $this->prop1->localized = true;

        $this->prop2 = new Property('prop_2');
        $this->prop3 = new Property('prop_3');
        $this->prop3->localized = true;
        $this->prop3->tags = array(array('name' => 'foobar'));
        $this->prop4 = new Property('prop_4');

        $this->section = new Section('section_1');
        $this->section->addChild($this->prop3);
        $this->section->addChild($this->prop4);

        $this->structure = new Structure();
        $this->structure->addChild($this->prop1);
        $this->structure->addChild($this->prop2);
        $this->structure->addChild($this->section);
    }

    public function testGetProperties()
    {
        $properties = $this->structure->getProperties();
        $this->assertEquals(array(
            'prop_1_localized' => $this->prop1,
            'prop_2' => $this->prop2,
            'prop_3' => $this->prop3,
            'prop_4' => $this->prop4,
        ), $properties);
    }

    public function testGetPropertiesByTag()
    {
        $result = $this->structure->getPropertiesByTag('foobar');
        $this->assertCount(1, $result);
        $property = reset($result);
        $this->assertSame($property, $this->prop3);
    }

    public function testTransformToModel()
    {
        $result = $this->structure->transformToModel();
        $this->assertInstanceOf(Structure::class, $result);
        $this->assertNotSame($result, $this->structure);
        $this->assertEquals(array(
            'prop_1_localized' => $this->prop1,
            'prop_2' => $this->prop2,
            'prop_3' => $this->prop3,
            'prop_4' => $this->prop4,
        ), $result->children);
    }
}
