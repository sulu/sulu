<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\FormMetadata\Validation;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\BlockFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\InvalidFieldMetadataException;

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

    public function testValidate()
    {
        $fieldMetadata = $this->prophesize(FieldMetadata::class);
        $fieldMetadata->getType()->willReturn('block');
        $fieldMetadata->getName()->willReturn('my_block');

        $type = $this->prophesize(FormMetadata::class);
        $fieldMetadata->getTypes()->willReturn([$type->reveal()]);

        $item = $this->prophesize(FieldMetadata::class);
        $item->getName()->willReturn('other');
        $type->getItems()->willReturn([$item->reveal()]);

        $this->expectNotToPerformAssertions();

        $this->blockFieldMetadataValidator->validate($fieldMetadata->reveal());
    }

    public function testValidatePropertyNameType()
    {
        $fieldMetadata = $this->prophesize(FieldMetadata::class);
        $fieldMetadata->getType()->willReturn('block');
        $fieldMetadata->getName()->willReturn('my_block');

        $type = $this->prophesize(FormMetadata::class);
        $fieldMetadata->getTypes()->willReturn([$type->reveal()]);

        $item = $this->prophesize(FieldMetadata::class);
        $item->getName()->willReturn('type');
        $type->getItems()->willReturn([$item->reveal()]);

        $this->expectException(InvalidFieldMetadataException::class);

        $this->blockFieldMetadataValidator->validate($fieldMetadata->reveal());
    }

    public function testValidatePropertyNameSettings()
    {
        $fieldMetadata = $this->prophesize(FieldMetadata::class);
        $fieldMetadata->getType()->willReturn('block');
        $fieldMetadata->getName()->willReturn('my_block');

        $type = $this->prophesize(FormMetadata::class);
        $fieldMetadata->getTypes()->willReturn([$type->reveal()]);

        $item = $this->prophesize(FieldMetadata::class);
        $item->getName()->willReturn('settings');
        $type->getItems()->willReturn([$item->reveal()]);

        $this->expectException(InvalidFieldMetadataException::class);

        $this->blockFieldMetadataValidator->validate($fieldMetadata->reveal());
    }

    public function testValidateItemNoFieldMetadata()
    {
        $fieldMetadata = $this->prophesize(FieldMetadata::class);
        $fieldMetadata->getType()->willReturn('block');
        $fieldMetadata->getName()->willReturn('my_block');

        $type = $this->prophesize(FormMetadata::class);
        $fieldMetadata->getTypes()->willReturn([$type->reveal()]);

        $item = $this->prophesize(ItemMetadata::class);
        $type->getItems()->willReturn([$item->reveal()]);

        $this->expectNotToPerformAssertions();

        $this->blockFieldMetadataValidator->validate($fieldMetadata->reveal());
    }

    public function testValidateNotBlock()
    {
        $fieldMetadata = $this->prophesize(FieldMetadata::class);
        $fieldMetadata->getType()->willReturn('other');
        $fieldMetadata->getName()->willReturn('my_other');

        $this->expectNotToPerformAssertions();

        $this->blockFieldMetadataValidator->validate($fieldMetadata->reveal());
    }
}
