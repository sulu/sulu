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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\ChainFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;

class ChainFieldMetadataValidatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FieldMetadataValidatorInterface>
     */
    private $fieldMetadataValidator1;

    /**
     * @var ObjectProphecy<FieldMetadataValidatorInterface>
     */
    private $fieldMetadataValidator2;

    /**
     * @var ChainFieldMetadataValidator
     */
    private $chainFieldMetadataValidator;

    protected function setUp(): void
    {
        $this->fieldMetadataValidator1 = $this->prophesize(FieldMetadataValidatorInterface::class);
        $this->fieldMetadataValidator2 = $this->prophesize(FieldMetadataValidatorInterface::class);

        $this->chainFieldMetadataValidator = new ChainFieldMetadataValidator([
            $this->fieldMetadataValidator1->reveal(),
            $this->fieldMetadataValidator2->reveal(),
        ]);
    }

    public function testValidate(): void
    {
        $fieldMetadata = new FieldMetadata('some_field');

        $this->fieldMetadataValidator1->validate($fieldMetadata, 'some_form_key')->shouldBeCalled();
        $this->fieldMetadataValidator2->validate($fieldMetadata, 'some_form_key')->shouldBeCalled();

        $this->chainFieldMetadataValidator->validate($fieldMetadata, 'some_form_key');
    }
}
