<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Property;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Structure\ManagedStructure;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class ManagedStructureTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var StructureMetadata
     */
    private $structureMetadata;

    /**
     * @var StructureBehavior
     */
    private $document;

    /**
     * @var ContentTypeInterface
     */
    private $contentType;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var PropertyMetadata
     */
    private $propertyMetadata;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var PropertyInterface
     */
    private $legacyProperty;

    /**
     * @var ManagedStructure
     */
    private $structure;

    public function setUp()
    {
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->document = $this->prophesize(StructureBehavior::class);
        $this->contentType = $this->prophesize(ContentTypeInterface::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->propertyMetadata = $this->prophesize(PropertyMetadata::class);
        $this->propertyFactory = $this->prophesize(LegacyPropertyFactory::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->legacyProperty = $this->prophesize(PropertyInterface::class);

        $this->structure = new ManagedStructure(
            $this->contentTypeManager->reveal(),
            $this->propertyFactory->reveal(),
            $this->inspector->reveal(),
            $this->document->reveal()
        );

        $this->inspector->getNode($this->document->reveal())->willReturn($this->node->reveal());
        $this->inspector->getStructureMetadata($this->document->reveal())->willReturn(
            $this->structureMetadata->reveal()
        );
    }

    /**
     * It should lazily initialize a localized property.
     */
    public function testGetLocalizedProperty()
    {
        $name = 'test';
        $contentTypeName = 'hello';
        $locale = 'de';

        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->propertyMetadata->isLocalized()->willReturn(true);

        $this->doGetProperty($name, $contentTypeName, $locale, true);
    }

    /**
     * It should bind values.
     */
    public function testBind()
    {
        $name = 'test';
        $contentTypeName = 'hello';
        $locale = 'de';

        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->propertyMetadata->isLocalized()->willReturn(true);

        $this->propertyMetadata->getType()->willReturn($contentTypeName);
        $this->structureMetadata->getProperties()->willReturn([$name => $this->propertyMetadata]);
        $this->structureMetadata->getProperty($name)->willReturn($this->propertyMetadata);
        $this->contentTypeManager->get($contentTypeName)->willReturn($this->contentType->reveal());

        $this->propertyFactory->createTranslatedProperty(
            $this->propertyMetadata->reveal(),
            $locale,
            Argument::type(StructureBridge::class)
        )->willReturn($this->legacyProperty->reveal());

        $this->inspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->inspector->getOriginalLocale($this->document->reveal())->willReturn($locale);

        $this->contentType->read(
            $this->node->reveal(),
            $this->legacyProperty->reveal(),
            'sulu_io',
            $locale,
            null
        )->shouldBeCalledTimes(1);

        $this->legacyProperty->setValue(1);

        $this->structure->bind([$name => 1]);
    }

    /**
     * It should bind also null values.
     */
    public function testBindNullValue()
    {
        $name = 'test';
        $contentTypeName = 'hello';
        $locale = 'de';

        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->propertyMetadata->isLocalized()->willReturn(true);

        $this->propertyMetadata->getType()->willReturn($contentTypeName);
        $this->structureMetadata->getProperties()->willReturn([$name => $this->propertyMetadata]);
        $this->structureMetadata->getProperty($name)->willReturn($this->propertyMetadata);
        $this->contentTypeManager->get($contentTypeName)->willReturn($this->contentType->reveal());

        $this->propertyFactory->createTranslatedProperty(
            $this->propertyMetadata->reveal(),
            $locale,
            Argument::type(StructureBridge::class)
        )->willReturn($this->legacyProperty->reveal());

        $this->inspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->inspector->getOriginalLocale($this->document->reveal())->willReturn($locale);

        $this->contentType->read(
            $this->node->reveal(),
            $this->legacyProperty->reveal(),
            'sulu_io',
            $locale,
            null
        )->shouldBeCalledTimes(1);

        $this->legacyProperty->setValue(null);

        $this->structure->bind([$name => null]);
    }

    /**
     * It should lazily initialize a non-localized property.
     */
    public function testGetNonLocalizedProperty()
    {
        $name = 'test';
        $contentTypeName = 'hello';
        $locale = 'de';

        $this->document->getLocale()->shouldNotBeCalled();
        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->propertyMetadata->isLocalized()->willReturn(false);

        $this->doGetProperty($name, $contentTypeName, $locale, false);
    }

    /**
     * It should act as an array.
     */
    public function testArrayAccess()
    {
        $name = 'test';
        $contentTypeName = 'hello';
        $locale = 'de';

        $this->document->getLocale()->shouldNotBeCalled();
        $this->propertyMetadata->isLocalized()->willReturn(false);
        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);

        $this->doGetProperty($name, $contentTypeName, $locale, false);
    }

    private function doGetProperty($name, $contentTypeName, $locale, $localized)
    {
        $this->propertyMetadata->getType()->willReturn($contentTypeName);
        $this->structureMetadata->getProperty($name)->willReturn($this->propertyMetadata);
        $this->contentTypeManager->get($contentTypeName)->willReturn($this->contentType->reveal());

        if ($localized) {
            $this->propertyFactory->createTranslatedProperty(
                $this->propertyMetadata->reveal(),
                $locale,
                Argument::type(StructureBridge::class)
            )->willReturn($this->legacyProperty->reveal());
        } else {
            $this->propertyFactory->createProperty($this->propertyMetadata->reveal())->willReturn(
                $this->legacyProperty->reveal()
            );
        }

        $this->inspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->inspector->getOriginalLocale($this->document->reveal())->willReturn($locale);

        $this->contentType->read(
            $this->node->reveal(),
            $this->legacyProperty->reveal(),
            'sulu_io',
            $locale,
            null
        )->shouldBeCalledTimes(1);

        $property = $this->structure->getProperty($name);

        $this->assertInstanceOf(PropertyValue::class, $property);
        $this->assertEquals($name, $property->getName());
    }
}
