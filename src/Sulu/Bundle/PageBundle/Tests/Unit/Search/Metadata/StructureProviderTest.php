<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Search\Metadata\StructureProvider;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;

class StructureProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataFactory>
     */
    private $metadataFactory;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureFactory;

    /**
     * @var ObjectProphecy<ExtensionManagerInterface>
     */
    private $extensionManager;

    /**
     * @var StructureProvider
     */
    private $provider;

    public function setUp(): void
    {
        $this->metadataFactory = $this->prophesize(MetadataFactory::class);
        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);

        $this->provider = new StructureProvider(
            new Factory(),
            $this->metadataFactory->reveal(),
            $this->structureFactory->reveal(),
            $this->extensionManager->reveal()
        );
    }

    /**
     * A "sulu.search.field" tagged property that lives inside a <section> of a referenced
     * global block must still be indexed. Flat global blocks were fixed by #8272, but
     * sectioned/nested ones regressed because getChildren() returns the raw section wrapper
     * instead of the flattened model properties.
     */
    public function testGlobalBlockWithSectionedSearchFieldIsIndexed(): void
    {
        // referenced global block "editorial": the searchable field is wrapped in a <section>
        $searchProperty = new PropertyMetadata('headline');
        $searchProperty->setType('text_line');
        $searchProperty->addTag(['name' => 'sulu.search.field', 'attributes' => []]);

        $section = new SectionMetadata('header');
        $section->addChild($searchProperty);

        $blockStructure = new StructureMetadata('editorial');
        $blockStructure->setChildren(['header' => $section]);
        $blockStructure->burnProperties();

        $this->structureFactory->getStructureMetadata('block', 'editorial')
            ->willReturn($blockStructure);

        // page template: a block property whose type references the global block (<type ref="editorial"/>)
        $globalBlockComponent = new ComponentMetadata('editorial');
        $globalBlockComponent->addTag([
            'name' => 'sulu.global_block',
            'attributes' => ['global_block' => 'editorial'],
        ]);

        $block = new BlockMetadata('blocks');
        $block->setType('block');
        $block->addComponent($globalBlockComponent);

        $structure = $this->prophesize(StructureMetadata::class);
        $structure->getProperties()->willReturn(['blocks' => $block]);

        $documentMetadata = $this->prophesize(Metadata::class);
        $documentMetadata->getClass()->willReturn(\stdClass::class);
        $documentMetadata->getReflectionClass()->willReturn(new \ReflectionClass(\stdClass::class));

        $classMetadata = $this->provider->getMetadata(
            $documentMetadata->reveal(),
            $structure->reveal()
        );

        $this->assertInstanceOf(ClassMetadata::class, $classMetadata);
        $fieldMapping = $classMetadata->getIndexMetadata('_default')->getFieldMapping();

        $this->assertArrayHasKey('blocks', $fieldMapping, 'The global block was not indexed at all.');

        $blockMapping = $fieldMapping['blocks'];
        $this->assertIsArray($blockMapping);
        $this->assertSame('complex', $blockMapping['type']);

        $complexMapping = $blockMapping['mapping'];
        $this->assertInstanceOf(ComplexMetadata::class, $complexMapping);

        $nestedMapping = $complexMapping->getFieldMapping();
        $this->assertIsArray($nestedMapping);
        $this->assertArrayHasKey(
            'editorial_headline',
            $nestedMapping,
            'The searchable field nested inside the global block section was not indexed.'
        );
    }
}
