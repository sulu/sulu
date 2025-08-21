<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Admin\PropertyMetadataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Page\Infrastructure\Sulu\Admin\PropertyMetadataMapper\PageTreeRoutePropertyMetadataMapper;

class PageTreeRoutePropertyMetadataMapperTest extends TestCase
{
    private PageTreeRoutePropertyMetadataMapper $pageTreeRoutePropertyMetadataMapper;

    public function setUp(): void
    {
        $this->pageTreeRoutePropertyMetadataMapper = new PageTreeRoutePropertyMetadataMapper();
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $jsonSchema = $this->pageTreeRoutePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                [
                    'type' => 'null',
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'page' => [
                            'anyOf' => [
                                ['type' => 'null'],
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'uuid' => ['type' => 'string'],
                                        'path' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                        'path' => ['type' => 'string'],
                        'suffix' => ['type' => 'string'],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->pageTreeRoutePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'page' => [
                    'type' => 'object',
                    'properties' => [
                        'uuid' => ['type' => 'string'],
                        'path' => ['type' => 'string'],
                    ],
                    'required' => ['uuid', 'path'],
                ],
                'path' => ['type' => 'string'],
                'suffix' => ['type' => 'string'],
            ],
            'required' => ['page'],
        ], $jsonSchema);
    }
}
