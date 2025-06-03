<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Validation;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\BlockFieldMetadataValidator;
use Sulu\Component\Content\Exception\ReservedPropertyNameException;

class BlockFieldMetadataValidatorTest extends TestCase
{
    /**
     * @var BlockFieldMetadataValidator
     */
    private $blockFieldMetadataValidator;

    protected function setUp(): void
    {
        $this->blockFieldMetadataValidator = new BlockFieldMetadataValidator();
    }

    /**
     * @param FormMetadata[] $types
     */
    private function createFieldMetadata(
        string $name,
        string $type,
        array $types = []
    ): FieldMetadata {
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

    private function expectReservedPropertyNameException(string $formKey, string $blockName, string $propertyName): void
    {
        $this->expectException(ReservedPropertyNameException::class);
        $this->expectExceptionMessageMatches('"' . $formKey . '"');
        $this->expectExceptionMessageMatches('"' . $blockName . '"');
        $this->expectExceptionMessageMatches('"' . $propertyName . '"');
    }

    public function testValidate(): void
    {
        $item = $this->createFieldMetadata('valid_name', 'text_line');
        $type = $this->createFormMetadata('some_block_type', [$item]);
        $block = $this->createFieldMetadata('some_block', 'block', [$type]);

        $this->expectNotToPerformAssertions();

        $this->blockFieldMetadataValidator->validate($block, 'some_form_key');
    }

    public function testValidatePropertyNameSettings(): void
    {
        $item = $this->createFieldMetadata('settings', 'text_line');
        $type = $this->createFormMetadata('some_block_type', [$item]);
        $block = $this->createFieldMetadata('some_block', 'block', [$type]);

        $this->expectReservedPropertyNameException('some_form_key', 'some_block', 'settings');

        $this->blockFieldMetadataValidator->validate($block, 'some_form_key');
    }

    public function testValidateBlockWithSectionWithSettingsProperty(): void
    {
        $item = $this->createFieldMetadata('settings', 'text_line');
        $section = $this->createSectionMetadata('some_section', [$item]);
        $type = $this->createFormMetadata('some_block_type', [$section]);
        $block = $this->createFieldMetadata('some_block', 'block', [$type]);

        $this->expectReservedPropertyNameException('some_form_key', 'some_block', 'settings');

        $this->blockFieldMetadataValidator->validate($block, 'some_form_key');
    }

    public function testValidateBlockWithSectionWithValidProperties(): void
    {
        $sectionItem = $this->createFieldMetadata('other_valid_name', 'text_line');
        $section = $this->createSectionMetadata('some_section', [$sectionItem]);
        $item = $this->createFieldMetadata('valid_name', 'text_line');
        $type = $this->createFormMetadata('some_block_type', [$item, $section]);
        $block = $this->createFieldMetadata('some_block', 'block', [$type]);

        $this->expectNotToPerformAssertions();

        $this->blockFieldMetadataValidator->validate($block, 'some_form_key');
    }

    public function testValidateNotBlock(): void
    {
        $property = $this->createFieldMetadata('some_property', 'text_line');

        $this->expectNotToPerformAssertions();

        $this->blockFieldMetadataValidator->validate($property, 'some_form_key');
    }
}
