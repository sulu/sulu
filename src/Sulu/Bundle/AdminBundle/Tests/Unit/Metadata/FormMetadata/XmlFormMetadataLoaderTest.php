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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;

class XmlFormMetadataLoaderTest extends TestCase
{
    use ProphecyTrait;

    public const CACHE_DIR = __DIR__ . '/../../../../../../../../tests/Resources/cache';

    /**
     * @var ObjectProphecy<FormXmlLoader>
     */
    private $formXmlLoader;

    /**
     * @var ObjectProphecy<FieldMetadataValidatorInterface>
     */
    private $fieldMetadataValidator;

    /**
     * @var XmlFormMetadataLoader
     */
    private $xmlFormMetadataLoader;

    protected function setUp(): void
    {
        $this->formXmlLoader = $this->prophesize(FormXmlLoader::class);
        $this->fieldMetadataValidator = $this->prophesize(FieldMetadataValidatorInterface::class);

        $this->xmlFormMetadataLoader = new XmlFormMetadataLoader(
            $this->formXmlLoader->reveal(),
            $this->fieldMetadataValidator->reveal(),
            [
                __DIR__ . '/dummy-forms',
            ],
            static::CACHE_DIR,
            false
        );
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
        $formMetadata = $this->createFormMetadata('some_form_key', [$propertyMetadata, $sectionMetadata, $blockMetadata]);

        $this->formXmlLoader->load(Argument::cetera())
            ->willReturn($formMetadata);

        $this->fieldMetadataValidator->validate($propertyMetadata, 'some_form_key')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($sectionPropertyMetadata, 'some_form_key')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($blockPropertyMetadata, 'some_form_key')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($blockMetadata, 'some_form_key')->shouldBeCalled();

        $this->xmlFormMetadataLoader->warmUp(static::CACHE_DIR);
    }
}
