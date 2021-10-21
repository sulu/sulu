<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface as NodePropertyInterface;
use PHPCR\PropertyType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Types\Number;

class NumberTest extends TestCase
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var Number
     */
    private $number;

    /**
     * @var NodeInterface|ObjectProphecy
     */
    private $node;

    /**
     * @var PropertyInterface|ObjectProphecy
     */
    private $property;

    /**
     * @var NodePropertyInterface|ObjectProphecy
     */
    private $nodeProperty;

    public function setUp(): void
    {
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->nodeProperty = $this->prophesize(NodePropertyInterface::class);

        $this->number = new Number($this->template);
    }

    public function testRead(): void
    {
        $content = 12.3;

        $this->node->hasProperty('i18n:de-test')->willReturn(true)->shouldBeCalled();
        $this->property->getName()->willReturn('i18n:de-test');
        $this->node->getPropertyValue('i18n:de-test', PropertyType::DOUBLE)->willReturn($content);

        $this->property->setValue($content)->shouldBeCalled();

        $this->number->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadWithoutExistingProperty(): void
    {
        $this->property->getName()->willReturn('i18n:de-test');
        $this->node->hasProperty('i18n:de-test')->willReturn(false)->shouldBeCalled();
        $this->node->getPropertyValue(Argument::any())->shouldNotBeCalled();

        $this->property->setValue(null)->shouldBeCalled();

        $this->number->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testWrite(): void
    {
        $content = 15;

        $this->property->getName()->willReturn('i18n:de-test');
        $this->property->getValue()->willReturn(15);

        $this->node->setProperty('i18n:de-test', $content, PropertyType::DOUBLE)->shouldBeCalled();
        $this->number->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteZero(): void
    {
        $content = 0;

        $this->property->getName()->willReturn('i18n:de-test');
        $this->property->getValue()->willReturn(0);

        $this->node->setProperty('i18n:de-test', $content, PropertyType::DOUBLE)->shouldBeCalled();
        $this->number->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteNoValue(): void
    {
        $this->property->getName()->willReturn('i18n:de-test');
        $this->property->getValue()->willReturn(null);
        $this->nodeProperty->remove()->shouldBeCalled();

        $this->node->hasProperty('i18n:de-test')->willReturn(true)->shouldBeCalled();
        $this->node->getProperty('i18n:de-test')->willReturn($this->nodeProperty->reveal())->shouldBeCalled();
        $this->number->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    /**
     * @return array<string, mixed>
     */
    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'number',
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfFloat(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'multiple_of', 'value' => 0.5],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'multipleOf' => 0.5,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfIntegerish(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'multiple_of', 'value' => '2'],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'integer',
                    'multipleOf' => 2.0,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfInteger(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'multiple_of', 'value' => 2],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'integer',
                    'multipleOf' => 2.0,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfTooSmall(): void
    {
        $this->expectExceptionMessage('Parameter "multiple_of" of property "property-name" needs to be greater than "0"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'multiple_of', 'value' => 0],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();
    }

    public function testMapPropertyMetadataMinAndMaxMultipleOfInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "multiple_of" of property "property-name" needs to be either null or numeric');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'multiple_of', 'value' => 'invalid-value'],
        ]);

        $this->number->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMax(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 3],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 2,
                    'maximum' => 3,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 2,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMaxOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 2],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'maximum' => 2,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxWithIntegerishValues(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => '2'],
            ['name' => 'max', 'value' => '3'],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 2,
                    'maximum' => 3,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxWithFloatValues(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 1.2],
            ['name' => 'max', 'value' => 3.4],
        ]);

        $jsonSchema = $this->number->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 1.2,
                    'maximum' => 3.4,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or numeric');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 'invalid-value'],
        ]);

        $this->number->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or numeric');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 'invalid-value'],
        ]);

        $this->number->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min" of property "property-name" has value "2", parameter "max" needs to be greater than or equal "2"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 1],
        ]);

        $this->number->mapPropertyMetadata($propertyMetadata);
    }
}
