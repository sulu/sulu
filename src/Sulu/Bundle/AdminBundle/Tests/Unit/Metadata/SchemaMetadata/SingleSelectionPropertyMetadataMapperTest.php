<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SingleSelectionPropertyMetadataMapper;
use Sulu\Component\Content\Metadata\PropertyMetadata;

class SingleSelectionPropertyMetadataMapperTest extends TestCase
{
    /**
     * @var SingleSelectionPropertyMetadataMapper
     */
    private $singleSelectionPropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->singleSelectionPropertyMetadataMapper = new SingleSelectionPropertyMetadataMapper();
    }

    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    private function getEmptyObjectSchema(): array
    {
        return [
            'type' => 'object',
            'maxProperties' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->singleSelectionPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyObjectSchema(),
                ['type' => 'string'],
                ['type' => 'number'],
                ['type' => 'object', 'minProperties' => 1],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->singleSelectionPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'number'],
                ['type' => 'object', 'minProperties' => 1],
            ],
        ], $jsonSchema);
    }
}
