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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\BlockSettingsFormMetadataVisitor;

class BlockSettingsFormMetadataVisitorTest extends TestCase
{
    private BlockSettingsFormMetadataVisitor $blockSettingsFormMetadataVisitor;

    public function setUp(): void
    {
        $this->blockSettingsFormMetadataVisitor = new BlockSettingsFormMetadataVisitor();
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

        $this->blockSettingsFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        $this->assertSame(
            [
                'settings_form_key' => [
                    'name' => 'settings_form_key',
                    'value' => 'page_block_settings',
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

    public function testVisitTypedFormMetadataKeep(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $otherBlockMetadata = new FieldMetadata('others');
        $otherBlockMetadata->setType('block');
        $optionMetadata = new OptionMetadata();
        $optionMetadata->setName('settings_form_key');
        $optionMetadata->setValue('other_block_settings');
        $otherBlockMetadata->addOption($optionMetadata);
        $formMetadata->addItem($otherBlockMetadata);

        $this->blockSettingsFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        $this->assertSame(
            [
                'settings_form_key' => [
                    'name' => 'settings_form_key',
                    'value' => 'other_block_settings',
                ],
            ],
            \array_map(function(OptionMetadata $optionMetadata): array {
                return [
                    'name' => $optionMetadata->getName(),
                    'value' => $optionMetadata->getValue(),
                ];
            }, $otherBlockMetadata->getOptions()),
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

        $this->blockSettingsFormMetadataVisitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en', []);

        $this->assertSame(
            [
                'settings_form_key' => [
                    'name' => 'settings_form_key',
                    'value' => 'page_block_settings',
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
}
