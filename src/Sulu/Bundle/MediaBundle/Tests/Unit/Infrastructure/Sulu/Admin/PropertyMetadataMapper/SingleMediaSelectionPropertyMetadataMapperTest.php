<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Admin\PropertyMetadataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\PropertyMetadataMapper\SingleMediaSelectionPropertyMetadataMapper;

class SingleMediaSelectionPropertyMetadataMapperTest extends TestCase
{
    private SingleMediaSelectionPropertyMetadataMapper $singleMediaSelectionPropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->singleMediaSelectionPropertyMetadataMapper = new SingleMediaSelectionPropertyMetadataMapper();
    }

    /**
     * @return array{type: 'null'}
     */
    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $jsonSchema = $this->singleMediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'anyOf' => [
                                $this->getNullSchema(),
                                ['type' => 'number'],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->singleMediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'number',
                ],
                'displayOption' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['id'],
        ], $jsonSchema);
    }
}
