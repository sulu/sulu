<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\StructureFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class StructureFormMetadataLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    public const CACHE_DIR = __DIR__ . '/../../../../../../../../tests/Resources/cache';

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureMetadataFactory;

    /**
     * @var ObjectProphecy<FormMetadataMapper>
     */
    private $formMetadataMapper;

    /**
     * @var ObjectProphecy<SchemaMetadataProvider>
     */
    private $schemaMetadataProvider;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<FieldMetadataValidatorInterface>
     */
    private $fieldMetadataValidator;

    /**
     * @var StructureFormMetadataLoader
     */
    private $structureFormMetadataLoader;

    protected function setUp(): void
    {
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->formMetadataMapper = $this->prophesize(FormMetadataMapper::class);
        $this->schemaMetadataProvider = $this->prophesize(SchemaMetadataProvider::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->fieldMetadataValidator = $this->prophesize(FieldMetadataValidatorInterface::class);

        $this->structureFormMetadataLoader = new StructureFormMetadataLoader(
            $this->structureMetadataFactory->reveal(),
            $this->formMetadataMapper->reveal(),
            $this->schemaMetadataProvider->reveal(),
            $this->webspaceManager->reveal(),
            $this->fieldMetadataValidator->reveal(),
            [],
            ['en'],
            static::CACHE_DIR,
            false
        );
    }

    /**
     * @param FormMetadata[] $types
     */
    private function createFieldMetadata(string $name, string $type, array $types = []): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata($name);
        $fieldMetadata->setType($type);

        foreach ($types as $type) {
            $fieldMetadata->addType($type);
        }

        return $fieldMetadata;
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function createFormMetadata(string $key, array $items = []): FormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        $formMetadata->setItems([]);

        foreach ($items as $item) {
            $formMetadata->addItem($item);
        }

        return $formMetadata;
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function createSectionMetadata(string $name, array $items = []): SectionMetadata
    {
        $sectionMetadata = new SectionMetadata($name);

        foreach ($items as $item) {
            $sectionMetadata->addItem($item);
        }

        return $sectionMetadata;
    }

    public function testWarmUp(): void
    {
        $propertyMetadata = $this->createFieldMetadata('some_property', 'text_line');
        $sectionPropertyMetadata = $this->createFieldMetadata('some_section_property', 'text_line');
        $sectionMetadata = $this->createSectionMetadata('some_section', [$sectionPropertyMetadata]);
        $blockPropertyMetadata = $this->createFieldMetadata('some_block_property', 'text_line');
        $blockTypeMetadata = $this->createFormMetadata('some_block_type_key', [$blockPropertyMetadata]);
        $blockMetadata = $this->createFieldMetadata('some_block', 'block', [$blockTypeMetadata]);

        $structure = new StructureMetadata();
        $structure->setName('some_template');
        $structure->setInternal(false);
        $structure->setTags([]);
        $structure->setChildren([]);
        $structure->burnProperties();

        if (!\file_exists(static::CACHE_DIR)) {
            \mkdir(static::CACHE_DIR);
        }

        $structure->setResource(\tempnam(static::CACHE_DIR, 'some_template'));

        $this->formMetadataMapper->mapTags([])->willReturn([]);
        $this->formMetadataMapper->mapChildren([])->willReturn([$propertyMetadata, $sectionMetadata, $blockMetadata]);

        $schemaMetadata = new SchemaMetadata();
        $this->schemaMetadataProvider->getMetadata(Argument::cetera())->willReturn($schemaMetadata);

        $this->structureMetadataFactory->getStructureTypes()->willReturn(['page']);
        $this->structureMetadataFactory->getStructures('page')->willReturn([$structure]);

        $this->fieldMetadataValidator->validate($propertyMetadata, 'some_template')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($sectionPropertyMetadata, 'some_template')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($blockPropertyMetadata, 'some_template')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($blockMetadata, 'some_template')->shouldBeCalled();

        $this->structureFormMetadataLoader->warmUp(static::CACHE_DIR);
    }
}
