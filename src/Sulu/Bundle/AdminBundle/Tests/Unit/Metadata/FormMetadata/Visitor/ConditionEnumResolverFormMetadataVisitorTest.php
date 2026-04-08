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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\ConditionEnumResolverFormMetadataVisitor;

class ConditionEnumResolverFormMetadataVisitorTest extends TestCase
{
    private ConditionEnumResolverFormMetadataVisitor $visitor;

    public function setUp(): void
    {
        $this->visitor = new ConditionEnumResolverFormMetadataVisitor();
    }

    public function testResolveEnumInVisibleCondition(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition("status == enum(Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor\TestStringStatus::ACTIVE)");
        $formMetadata->addItem($field);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $this->assertSame("status == 'active'", $field->getVisibleCondition());
    }

    public function testResolveIntEnumInDisabledCondition(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setDisabledCondition("priority == enum(Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor\TestIntPriority::HIGH)");
        $formMetadata->addItem($field);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $this->assertSame('priority == 3', $field->getDisabledCondition());
    }

    public function testResolveConstInCondition(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition("type == const(Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor\TestConstants::TYPE_DRAFT)");
        $formMetadata->addItem($field);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $this->assertSame("type == 'draft'", $field->getVisibleCondition());
    }

    public function testResolveMultipleExpressionsInCondition(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition("status == enum(Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor\TestStringStatus::ACTIVE) && priority == enum(Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor\TestIntPriority::HIGH)");
        $formMetadata->addItem($field);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $this->assertSame("status == 'active' && priority == 3", $field->getVisibleCondition());
    }

    public function testResolveInSectionItems(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $section = new SectionMetadata('details');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition("status == enum(Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor\TestStringStatus::ACTIVE)");
        $section->addItem($field);

        $formMetadata->addItem($section);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $this->assertSame("status == 'active'", $field->getVisibleCondition());
    }

    public function testResolveInTypedFormMetadata(): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition("status == enum(Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor\TestStringStatus::ACTIVE)");
        $formMetadata->addItem($field);

        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $this->visitor->visitTypedFormMetadata($typedFormMetadata, 'page', 'en');

        $this->assertSame("status == 'active'", $field->getVisibleCondition());
    }

    public function testNoConditionLeftUntouched(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition("status == 'active'");
        $formMetadata->addItem($field);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $this->assertSame("status == 'active'", $field->getVisibleCondition());
    }

    public function testNullConditionLeftUntouched(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $formMetadata->addItem($field);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($field->getVisibleCondition());
        $this->assertNull($field->getDisabledCondition());
    }

    public function testInvalidEnumClassThrowsException(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition('status == enum(App\NonExistentEnum::CASE)');
        $formMetadata->addItem($field);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $this->visitor->visitFormMetadata($formMetadata, 'en');
    }

    public function testInvalidConstThrowsException(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $field = new FieldMetadata('title');
        $field->setType('text_line');
        $field->setVisibleCondition('status == const(App\NonExistent::CONST)');
        $formMetadata->addItem($field);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not defined');

        $this->visitor->visitFormMetadata($formMetadata, 'en');
    }
}

enum TestStringStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum TestIntPriority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}

class TestConstants
{
    public const TYPE_DRAFT = 'draft';
    public const TYPE_PUBLISHED = 'published';
}