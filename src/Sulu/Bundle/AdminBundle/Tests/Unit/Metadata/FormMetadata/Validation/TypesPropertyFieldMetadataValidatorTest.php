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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\TypesPropertyFieldMetadataValidator;
use Sulu\Component\Content\Exception\ReservedPropertyNameException;

class TypesPropertyFieldMetadataValidatorTest extends TestCase
{
    /**
     * @var TypesPropertyFieldMetadataValidator
     */
    private $propertyFieldMetadataValidator;

    protected function setUp(): void
    {
        $this->propertyFieldMetadataValidator = new TypesPropertyFieldMetadataValidator();
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

    private function expectReservedPropertyNameException(string $formKey, string $propertyName, string $subPropertyName): void
    {
        $this->expectException(ReservedPropertyNameException::class);
        $this->expectExceptionMessageMatches('"' . $formKey . '"');
        $this->expectExceptionMessageMatches('"' . $propertyName . '"');
        $this->expectExceptionMessageMatches('"' . $subPropertyName . '"');
    }

    public function testValidate(): void
    {
        $item = $this->createFieldMetadata('valid_name', 'text_line');
        $type = $this->createFormMetadata('some_property_type', [$item]);
        $property = $this->createFieldMetadata('some_property', 'text_line', [$type]);

        $this->expectNotToPerformAssertions();

        $this->propertyFieldMetadataValidator->validate($property, 'some_form_key');
    }

    public function testValidatePropertyNameType(): void
    {
        $item = $this->createFieldMetadata('type', 'text_line');
        $type = $this->createFormMetadata('some_property_type', [$item]);
        $property = $this->createFieldMetadata('some_property', 'text_line', [$type]);

        $this->expectReservedPropertyNameException('some_form_key', 'some_property', 'type');

        $this->propertyFieldMetadataValidator->validate($property, 'some_form_key');
    }

    public function testValidateTypesPropertyWithSectionWithTypeProperty(): void
    {
        $item = $this->createFieldMetadata('type', 'text_line');
        $section = $this->createSectionMetadata('some_section', [$item]);
        $type = $this->createFormMetadata('some_property_type', [$section]);
        $property = $this->createFieldMetadata('some_property', 'text_line', [$type]);

        $this->expectReservedPropertyNameException('some_form_key', 'some_property', 'type');

        $this->propertyFieldMetadataValidator->validate($property, 'some_form_key');
    }

    public function testValidateTypesPropertyWithSectionWithValidProperties(): void
    {
        $sectionItem = $this->createFieldMetadata('other_valid_name', 'text_line');
        $section = $this->createSectionMetadata('some_section', [$sectionItem]);
        $item = $this->createFieldMetadata('valid_name', 'text_line');
        $type = $this->createFormMetadata('some_property_type', [$item, $section]);
        $property = $this->createFieldMetadata('some_property', 'text_line', [$type]);

        $this->expectNotToPerformAssertions();

        $this->propertyFieldMetadataValidator->validate($property, 'some_form_key');
    }
}
