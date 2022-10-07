<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Compat\Structure;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class StructureBridgeTest extends TestCase
{
    use ProphecyTrait;

    public function testCopyFrom(): void
    {
        $titleProperty = $this->prophesize(PropertyMetadata::class);
        $titleProperty->getName()->willReturn('title');
        $imagesProperty = $this->prophesize(PropertyMetadata::class);
        $imagesProperty->getName()->willReturn('images');

        $title = $this->prophesize(Property::class);
        $title->getName()->willReturn('title');
        $images = $this->prophesize(Property::class);
        $images->getName()->willReturn('images');

        $title->setValue('test-title')->shouldBeCalled();
        $images->setValue(['ids' => [1, 2, 3, 4]])->shouldBeCalled();

        $document = $this->prophesize(StructureBehavior::class);
        $metadata = $this->prophesize(StructureMetadata::class);
        $copyFromStructure = $this->prophesize(StructureBridge::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $reveal = $copyFromStructure->reveal();
        $property = new \ReflectionProperty(\get_class($reveal), 'document');
        $property->setAccessible(true);
        $property->setValue($reveal, $document->reveal());

        $metadata->getProperties()->willReturn([$titleProperty, $imagesProperty]);
        $metadata->hasProperty('title')->willReturn(true);
        $metadata->getProperty('title')->willReturn($title->reveal());
        $metadata->hasProperty('images')->willReturn(true);
        $metadata->getProperty('images')->willReturn($images->reveal());

        $copyFromStructure->getDocument()->willReturn($document);
        $copyFromStructure->hasProperty('title')->willReturn(true);
        $copyFromStructure->hasProperty('images')->willReturn(true);
        $copyFromStructure->getPropertyValue('title')->willReturn('test-title');
        $copyFromStructure->getPropertyValue('images')->willReturn(['ids' => [1, 2, 3, 4]]);

        $propertyFactory->createProperty(
            Argument::type(PropertyMetadata::class),
            Argument::type(StructureBridge::class)
        )->will(
            function($args) use ($title, $images) {
                if ('title' === $args[0]->getName()) {
                    return $title->reveal();
                } elseif ('images' === $args[0]->getName()) {
                    return $images->reveal();
                }
            }
        );
        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal()
        );

        $structure->copyFrom($copyFromStructure->reveal());
    }

    public function testGetNodeName(): void
    {
        $metadata = $this->prophesize(StructureMetadata::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $document = $this->prophesize(SnippetDocument::class);
        $document->getTitle()->willReturn('test');

        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal(),
            $document->reveal()
        );

        $this->assertEquals('test', $structure->getNodeName());
    }

    public function testGetNodeNameForInternalLink(): void
    {
        $metadata = $this->prophesize(StructureMetadata::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $redirectDocument = $this->prophesize(BasePageDocument::class);
        $redirectDocument->getTitle()->willReturn('test');

        $document = $this->prophesize(BasePageDocument::class);
        $document->getTitle()->shouldNotBeCalled();
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectDocument->reveal());

        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal(),
            $document->reveal()
        );

        $this->assertEquals('test', $structure->getNodeName());
    }

    public function testGetIsShadow(): void
    {
        $metadata = $this->prophesize(StructureMetadata::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $document = $this->prophesize(BasePageDocument::class);
        $document->isShadowLocaleEnabled()->willReturn(true);
        $document->getShadowLocale()->willReturn('de');

        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal(),
            $document->reveal()
        );

        $this->assertTrue($structure->getIsShadow());
        $this->assertEquals('de', $structure->getShadowBaseLanguage());
    }

    public function testGetIsShadowWrongDocument(): void
    {
        $metadata = $this->prophesize(StructureMetadata::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $document = $this->prophesize(\stdClass::class);

        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal(),
            $document->reveal()
        );

        $this->assertFalse($structure->getIsShadow());
        $this->assertNull($structure->getShadowBaseLanguage());
    }

    public function testGetExt(): void
    {
        $ext = [
            'seo' => [],
            'excerpt' => [],
        ];

        $metadata = $this->prophesize(StructureMetadata::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $document = $this->prophesize(\stdClass::class);
        $document->willImplement(ExtensionBehavior::class);
        $document->getExtensionsData()->willReturn($ext);

        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal(),
            $document->reveal()
        );

        $this->assertSame($ext, $structure->getExt());
    }

    public function testWithoutDocument(): void
    {
        $metadata = $this->prophesize(StructureMetadata::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal(),
            null
        );

        $this->assertNull($structure->getWebspaceKey());
        $this->assertFalse($structure->getIsShadow());
    }
}
