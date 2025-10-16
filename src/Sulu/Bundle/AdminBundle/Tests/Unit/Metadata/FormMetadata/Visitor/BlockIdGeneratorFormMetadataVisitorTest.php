<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\BlockIdGeneratorFormMetadataVisitor;

class BlockIdGeneratorFormMetadataVisitorTest extends TestCase
{
    private BlockIdGeneratorFormMetadataVisitor $blockIdGeneratorFormMetadataVisitor;

    public function setUp(): void
    {
        $this->blockIdGeneratorFormMetadataVisitor = new BlockIdGeneratorFormMetadataVisitor();
    }

    public function testVisitTypedFormMetadata(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $blockMetadata = new FieldMetadata('blocks');
        $blockMetadata->setType('block');
        $formMetadata->addItem($blockMetadata);

        $this->blockIdGeneratorFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        $this->assertSame(
            [
                'block_id_generator' => [
                    'name' => 'block_id_generator',
                    'value' => true,
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $blockMetadata->getOptions()),
        );
    }

    public function testVisitTypedFormMetadataKeepExisting(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $blockMetadata = new FieldMetadata('blocks');
        $blockMetadata->setType('block');
        $optionMetadata = new OptionMetadata();
        $optionMetadata->setName('block_id_generator');
        $optionMetadata->setValue(false);
        $blockMetadata->addOption($optionMetadata);
        $formMetadata->addItem($blockMetadata);

        $this->blockIdGeneratorFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        // Should not override existing option
        $this->assertSame(
            [
                'block_id_generator' => [
                    'name' => 'block_id_generator',
                    'value' => false,
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $blockMetadata->getOptions()),
        );
    }

    public function testVisitTypedFormMetadataNested(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $blockMetadata = new FieldMetadata('blocks');
        $blockMetadata->setType('block');
        $formMetadata->addItem($blockMetadata);

        $nestedBlockMetadata = new FieldMetadata('nested');
        $nestedBlockMetadata->setType('block');

        $blockTypeMetadata = new FormMetadata();
        $blockTypeMetadata->setKey('default');
        $blockTypeMetadata->addItem($nestedBlockMetadata);

        $blockMetadata->addType($blockTypeMetadata);

        $this->blockIdGeneratorFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        // Parent block should have option
        $this->assertSame(
            [
                'block_id_generator' => [
                    'name' => 'block_id_generator',
                    'value' => true,
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $blockMetadata->getOptions()),
        );

        // Nested block should also have option
        $this->assertSame(
            [
                'block_id_generator' => [
                    'name' => 'block_id_generator',
                    'value' => true,
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $nestedBlockMetadata->getOptions()),
        );
    }

    public function testVisitTypedFormMetadataWithSections(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $sectionMetadata = new SectionMetadata('section1');
        $formMetadata->addItem($sectionMetadata);

        $blockMetadata = new FieldMetadata('blocks');
        $blockMetadata->setType('block');
        $sectionMetadata->addItem($blockMetadata);

        $this->blockIdGeneratorFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        // Block in section should have option
        $this->assertSame(
            [
                'block_id_generator' => [
                    'name' => 'block_id_generator',
                    'value' => true,
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $blockMetadata->getOptions()),
        );
    }

    public function testVisitTypedFormMetadataMultipleBlocks(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $blockMetadata1 = new FieldMetadata('blocks1');
        $blockMetadata1->setType('block');
        $formMetadata->addItem($blockMetadata1);

        $blockMetadata2 = new FieldMetadata('blocks2');
        $blockMetadata2->setType('block');
        $formMetadata->addItem($blockMetadata2);

        $this->blockIdGeneratorFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        // Both blocks should have option
        $this->assertSame(
            [
                'block_id_generator' => [
                    'name' => 'block_id_generator',
                    'value' => true,
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $blockMetadata1->getOptions()),
        );

        $this->assertSame(
            [
                'block_id_generator' => [
                    'name' => 'block_id_generator',
                    'value' => true,
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $blockMetadata2->getOptions()),
        );
    }

    public function testVisitTypedFormMetadataNonBlockField(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $textMetadata = new FieldMetadata('title');
        $textMetadata->setType('text_line');
        $formMetadata->addItem($textMetadata);

        $this->blockIdGeneratorFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        // Non-block fields should not have the option
        $this->assertEmpty($textMetadata->getOptions());
    }
}
