<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Compat\Structure;

use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Compat\Block\BlockPropertyInterface;
use Sulu\Component\Content\Compat\PropertyInterface as LegacyPropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Section\SectionPropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\DocumentManager\NamespaceRegistry;

class LegacyPropertyFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    /**
     * @var LegacyPropertyFactory
     */
    private $factory;

    /**
     * @var PropertyMetadata
     */
    private $property1;

    /**
     * @var PropertyMetadata
     */
    private $property2;

    /**
     * @var SectionMetadata
     */
    private $section;

    /**
     * @var BlockMetadata
     */
    private $block;

    /**
     * @var ComponentMetadata
     */
    private $component;

    public function setUp()
    {
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->factory = new LegacyPropertyFactory(
            $this->namespaceRegistry->reveal()
        );

        $this->property1 = $this->prophesize(PropertyMetadata::class);
        $this->property2 = $this->prophesize(PropertyMetadata::class);
        $this->section = $this->prophesize(SectionMetadata::class);
        $this->block = $this->prophesize(BlockMetadata::class);
        $this->component = $this->prophesize(ComponentMetadata::class);
    }

    /**
     * It should create standard properties from "new" properties.
     */
    public function testCreateProperty()
    {
        $name = 'foo';
        $title = ['de' => 'Tite'];
        $description = ['de' => 'Description'];
        $placeholder = ['de' => 'Placehodler'];
        $type = 'type';
        $required = true;
        $localized = true;
        $maxOccurs = 1;
        $minOccurs = 1;
        $parameters = [
            [
                'name' => 'prop',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
            [
                'name' => 'propfoo',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
        ];
        $colSpan = 6;

        $this->property1->getType()->willReturn($type);
        $this->property1->getName()->willReturn($name);
        $this->property1->isRequired()->willReturn($required);
        $this->property1->isLocalized()->willReturn($required);
        $this->property1->getMaxOccurs()->willReturn($maxOccurs);
        $this->property1->getMinOccurs()->willReturn($minOccurs);
        $this->property1->getColSpan()->willReturn($colSpan);
        $this->property1->getParameters()->willReturn($parameters);
        $this->property1->title = $title;
        $this->property1->description = $description;
        $this->property1->placeholder = $placeholder;

        $legacyProperty = $this->factory->createProperty($this->property1->reveal());

        $this->assertInstanceOf(LegacyPropertyInterface::class, $legacyProperty);
        $this->assertEquals($legacyProperty->getContentTypeName(), $type);
        $this->assertEquals($legacyProperty->getName(), $name);
        $this->assertEquals($legacyProperty->getMandatory(), $required);
        $this->assertEquals($legacyProperty->getMultilingual(), $localized);
        $this->assertEquals($legacyProperty->getMaxOccurs(), $maxOccurs);
        $this->assertEquals($legacyProperty->getMinOccurs(), $minOccurs);
        $this->assertEquals($legacyProperty->getColspan(), $colSpan);
        $this->assertContainsOnlyInstancesOf(PropertyParameter::class, $legacyProperty->getParams());
        $this->assertArrayHasKey('prop', $legacyProperty->getParams());
        $this->assertArrayHasKey('propfoo', $legacyProperty->getParams());
        $this->assertEquals($title['de'], $legacyProperty->getTitle('de'));
        $this->assertEquals($description['de'], $legacyProperty->getInfoText('de'));
        $this->assertEquals($placeholder['de'], $legacyProperty->getPlaceholder('de'));

        return $this->property1;
    }

    /**
     * It should create a translated property.
     *
     * @depends testCreateProperty
     */
    public function testCreateTranslated()
    {
        $this->setUpProperty($this->property1);
        $this->namespaceRegistry->getPrefix('content_localized')->willReturn('i18n');
        $translatedProperty = $this->factory->createTranslatedProperty($this->property1->reveal(), 'de');
        $this->assertEquals('de', $translatedProperty->getLocalization());
        $this->assertEquals('i18n:de-name', $translatedProperty->getName());
    }

    /**
     * It should create a section property.
     *
     * @depends testCreateProperty
     */
    public function testCreateSection($property)
    {
        $name = 'foo';
        $parameters = ['foo', 'bar'];
        $colSpan = 6;
        $title = ['de' => 'Tite'];
        $description = ['de' => 'Description'];

        $this->section->getName()->willReturn($name);
        $this->section->getColSpan()->willReturn($colSpan);
        $this->section->getParameters()->willReturn($parameters);
        $this->section->title = $title;
        $this->section->description = $description;
        $this->section->getChildren()->willReturn([
            $property->reveal(),
        ]);

        $legacyProperty = $this->factory->createProperty($this->section->reveal());

        $this->assertInstanceOf(SectionPropertyInterface::class, $legacyProperty);
        $this->assertEquals($name, $legacyProperty->getName());
        $this->assertEquals($colSpan, $legacyProperty->getColspan());
        $this->assertEquals($title['de'], $legacyProperty->getTitle('de'));
        $this->assertEquals($description['de'], $legacyProperty->getInfoText('de'));
        $this->assertCount(1, $legacyProperty->getChildProperties());
    }

    /**
     * It should create a block property.
     *
     * @depends testCreateProperty
     */
    public function testCreateBlock($property)
    {
        $this->setUpProperty($this->block);

        $this->component->getName()->willReturn('hai');
        $this->component->getChildren()->willReturn([
            $property->reveal(),
        ]);
        $this->component->title = [
            'de' => 'Testtitel',
            'en' => 'Test title',
        ];
        $this->block->getComponents()->willReturn([
            $this->component->reveal(),
        ]);
        $this->block->getDefaultComponentName()->willReturn('foobar');

        /** @var BlockProperty $blockProperty */
        $blockProperty = $this->factory->createProperty($this->block->reveal());

        $this->assertInstanceOf(BlockPropertyInterface::class, $blockProperty);
        $this->assertCount(1, $blockProperty->getTypes());
        $blockType = $blockProperty->getType('hai');
        $this->assertNotNull($blockType);
        $this->assertCount(1, $blockType->getChildProperties());
        $this->assertEquals('Testtitel', $blockType->getMetadata()->get('title', 'de'));
        $this->assertEquals('Test title', $blockType->getMetadata()->get('title', 'en'));
    }

    private function setUpProperty($property)
    {
        $name = 'name';
        $title = ['de' => 'Tite'];
        $description = ['de' => 'Description'];
        $placeholder = ['de' => 'Placehodler'];
        $type = 'type';
        $required = true;
        $localized = true;
        $maxOccurs = 1;
        $minOccurs = 1;
        $parameters = [
            [
                'name' => 'prop',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
            [
                'name' => 'propfoo',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
        ];
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
        $property->getChildren()->willReturn([
            $this->component->reveal(),
        ]);
    }
}
