<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\MediaBundle\Metadata\ImageMapFieldMetadataValidator;
use Sulu\Component\Content\Exception\ReservedPropertyNameException;

class ImageMapFieldMetadataValidatorTest extends TestCase
{
    /**
     * @var ImageMapFieldMetadataValidator
     */
    private $imageMapFieldMetadataValidator;

    protected function setUp(): void
    {
        $this->imageMapFieldMetadataValidator = new ImageMapFieldMetadataValidator();
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

    private function expectReservedPropertyNameException(string $formKey, string $imageMapName, string $propertyName): void
    {
        $this->expectException(ReservedPropertyNameException::class);
        $this->expectExceptionMessageMatches('"' . $formKey . '"');
        $this->expectExceptionMessageMatches('"' . $imageMapName . '"');
        $this->expectExceptionMessageMatches('"' . $propertyName . '"');
    }

    public function testValidate(): void
    {
        $item = $this->createFieldMetadata('valid_name', 'text_line');
        $type = $this->createFormMetadata('some_image_map_type', [$item]);
        $imageMap = $this->createFieldMetadata('some_image_map', 'image_map', [$type]);

        $this->expectNotToPerformAssertions();

        $this->imageMapFieldMetadataValidator->validate($imageMap, 'some_form_key');
    }

    public function testValidatePropertyNameHotspot(): void
    {
        $item = $this->createFieldMetadata('hotspot', 'text_line');
        $type = $this->createFormMetadata('some_image_map_type', [$item]);
        $imageMap = $this->createFieldMetadata('some_image_map', 'image_map', [$type]);

        $this->expectReservedPropertyNameException('some_form_key', 'some_image_map', 'hotspot');

        $this->imageMapFieldMetadataValidator->validate($imageMap, 'some_form_key');
    }

    public function testValidateImageMapWithSectionWithHotspotProperty(): void
    {
        $item = $this->createFieldMetadata('hotspot', 'text_line');
        $section = $this->createSectionMetadata('some_section', [$item]);
        $type = $this->createFormMetadata('some_image_map_type', [$section]);
        $imageMap = $this->createFieldMetadata('some_image_map', 'image_map', [$type]);

        $this->expectReservedPropertyNameException('some_form_key', 'some_image_map', 'hotspot');

        $this->imageMapFieldMetadataValidator->validate($imageMap, 'some_form_key');
    }

    public function testValidateImageMapWithSectionWithValidProperties(): void
    {
        $sectionItem = $this->createFieldMetadata('other_valid_name', 'text_line');
        $section = $this->createSectionMetadata('some_section', [$sectionItem]);
        $item = $this->createFieldMetadata('valid_name', 'text_line');
        $type = $this->createFormMetadata('some_image_map_type', [$item, $section]);
        $imageMap = $this->createFieldMetadata('some_image_map', 'image_map', [$type]);

        $this->expectNotToPerformAssertions();

        $this->imageMapFieldMetadataValidator->validate($imageMap, 'some_form_key');
    }

    public function testValidateNotImageMap(): void
    {
        $property = $this->createFieldMetadata('some_property', 'text_line');

        $this->expectNotToPerformAssertions();

        $this->imageMapFieldMetadataValidator->validate($property, 'some_form_key');
    }
}
