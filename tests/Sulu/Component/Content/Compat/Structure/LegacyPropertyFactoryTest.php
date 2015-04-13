<?php

namespace Sulu\Component\Content\Compat\Block\Structure;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Content\Compat\PropertyInterface as LegacyPropertyInterface;
use Sulu\Component\Content\Structure\Section;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Compat\Section\SectionPropertyInterface;
use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Structure\Block;
use Sulu\Component\Content\Structure\Component;
use Sulu\Component\Content\Compat\Block\BlockPropertyInterface;
use Sulu\Component\DocumentManager\NamespaceRegistry;

class LegacyPropertyFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->factory = new LegacyPropertyFactory(
            $this->namespaceRegistry->reveal()
        );

        $this->property = $this->prophesize(Property::class);
        $this->property1 = $this->prophesize(Property::class);
        $this->section = $this->prophesize(Section::class);
        $this->block = $this->prophesize(Block::class);
        $this->component = $this->prophesize(Component::class);
    }

    /**
     * It should create standard properties from "new" properties
     */
    public function testCreateProperty()
    {
        $name = 'foo';
        $title = array('de' => 'Tite');
        $description = array('de' => 'Description');
        $placeholder = array('de' => 'Placehodler');
        $type = 'type';
        $required = true;
        $localized = true;
        $maxOccurs = 1;
        $minOccurs = 1;
        $parameters = array('foo', 'bar');
        $colSpan = 6;

        $this->property->getType()->willReturn($type);
        $this->property->getName()->willReturn($name);
        $this->property->isRequired()->willReturn($required);
        $this->property->isLocalized()->willReturn($required);
        $this->property->getMaxOccurs()->willReturn($maxOccurs);
        $this->property->getMinOccurs()->willReturn($minOccurs);
        $this->property->getColSpan()->willReturn($colSpan);
        $this->property->getParameters()->willReturn($parameters);
        $this->property->title = $title;
        $this->property->description = $description;
        $this->property->placeholder = $placeholder;

        $legacyProperty = $this->factory->createProperty($this->property->reveal());

        $this->assertInstanceOf(LegacyPropertyInterface::class, $legacyProperty);
        $this->assertEquals($legacyProperty->getContentTypeName(), $type);
        $this->assertEquals($legacyProperty->getName(), $name);
        $this->assertEquals($legacyProperty->getMandatory(), $required);
        $this->assertEquals($legacyProperty->getMultilingual(), $localized);
        $this->assertEquals($legacyProperty->getMaxOccurs(), $maxOccurs);
        $this->assertEquals($legacyProperty->getMinOccurs(), $minOccurs);
        $this->assertEquals($legacyProperty->getParams(), $parameters);
        $this->assertEquals($legacyProperty->getColspan(), $colSpan);
        $this->assertEquals($legacyProperty->getParams(), $parameters);
        $this->assertEquals($title['de'], $legacyProperty->getTitle('de'));
        $this->assertEquals($description['de'], $legacyProperty->getInfoText('de'));
        $this->assertEquals($placeholder['de'], $legacyProperty->getPlaceholder('de'));

        return $this->property;
    }

    /**
     * It should create a translated property
     *
     * @depends testCreateProperty
     */
    public function testCreateTranslated()
    {
        $this->setUpProperty($this->property);
        $this->namespaceRegistry->getPrefix('content_localized')->willReturn('i18n');
        $translatedProperty = $this->factory->createTranslatedProperty($this->property->reveal(), 'de');
        $this->assertEquals('de', $translatedProperty->getLocalization());
        $this->assertEquals('i18n:de-name', $translatedProperty->getName());
    }

    /**
     * It should create a section property
     *
     * @depends testCreateProperty
     */
    public function testCreateSection($property)
    {
        $name = 'foo';
        $parameters = array('foo', 'bar');
        $colSpan = 6;
        $title = array('de' => 'Tite');
        $description = array('de' => 'Description');

        $this->section->getName()->willReturn($name);
        $this->section->getColSpan()->willReturn($colSpan);
        $this->section->getParameters()->willReturn($parameters);
        $this->section->title = $title;
        $this->section->description = $description;
        $this->section->getChildren()->willReturn(array(
            $property->reveal()
        ));

        $legacyProperty = $this->factory->createProperty($this->section->reveal());

        $this->assertInstanceOf(SectionPropertyInterface::class, $legacyProperty);
        $this->assertEquals($name, $legacyProperty->getName());
        $this->assertEquals($colSpan, $legacyProperty->getColspan());
        $this->assertEquals($title['de'], $legacyProperty->getTitle('de'));
        $this->assertEquals($description['de'], $legacyProperty->getInfoText('de'));
        $this->assertCount(1, $legacyProperty->getChildProperties());
    }

    /**
     * It should create a block property
     *
     * @depends testCreateProperty
     */
    public function testCreateBlock($property)
    {
        $this->setUpProperty($this->block);

        $this->component->getName()->willReturn('hai');
        $this->component->getChildren()->willReturn(array(
            $property->reveal()
        ));

        $blockProperty = $this->factory->createProperty($this->block->reveal());

        $this->assertInstanceOf(BlockPropertyInterface::class, $blockProperty);
        $this->assertCount(1, $blockProperty->getTypes());
        $blockType = $blockProperty->getType('hai');
        $this->assertNotNull($blockType);
        $this->assertCount(1, $blockType->getChildProperties());
    }

    private function setUpProperty($property)
    {
        $name = 'name';
        $title = array('de' => 'Tite');
        $description = array('de' => 'Description');
        $placeholder = array('de' => 'Placehodler');
        $type = 'type';
        $required = true;
        $localized = true;
        $maxOccurs = 1;
        $minOccurs = 1;
        $parameters = array('foo', 'bar');
        $colSpan = 6;

        $property->getType()->willReturn($type);
        $property->getName()->willReturn($name);
        $property->isRequired()->willReturn($required);
        $property->isLocalized()->willReturn($localized);
        $property->getMaxOccurs()->willReturn($maxOccurs);
        $property->getMinOccurs()->willReturn($minOccurs);
        $property->getColSpan()->willReturn($colSpan);
        $property->getParameters()->willReturn($parameters);
        $property->title = $title;
        $property->description = $description;
        $property->placeholder = $placeholder;
        $property->getChildren()->willReturn(array(
            $this->component->reveal(),
        ));
    }
}
