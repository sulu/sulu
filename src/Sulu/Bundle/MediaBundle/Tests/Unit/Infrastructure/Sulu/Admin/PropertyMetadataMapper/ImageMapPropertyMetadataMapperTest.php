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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata as SchemaPropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\PropertyMetadataMapper\ImageMapPropertyMetadataMapper;

class ImageMapPropertyMetadataMapperTest extends TestCase
{
    use ProphecyTrait;

    private ImageMapPropertyMetadataMapper $imageMapPropertyMetadataMapper;

    /**
     * @var ObjectProphecy<SchemaMetadataProvider>
     */
    private $schemaMetadataProvider;

    protected function setUp(): void
    {
        $this->schemaMetadataProvider = $this->prophesize(SchemaMetadataProvider::class);

        $this->imageMapPropertyMetadataMapper = new ImageMapPropertyMetadataMapper(
            $this->schemaMetadataProvider->reveal(),
        );
    }

    public function testMapPropertyMetadata(): void
    {
        $types = [
            'headline' => [
                'isGlobalBlock' => true,
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $metadata = new FieldMetadata('imageMap');
        $metadata->setRequired(true);
        foreach ($types as $key => $config) {
            $type = new FormMetadata();
            $type->setKey($key);

            $isGlobalBlock = $config['isGlobalBlock'] ?? false;
            if ($isGlobalBlock) {
                $tagMetadata = new TagMetadata();
                $tagMetadata->setName('sulu.global_block');
                $tagMetadata->setAttributes([
                    'global_block' => $key,
                ]);
                $type->addTag($tagMetadata);
            }

            foreach ($config['children'] ?? [] as $childName => $childType) {
                $itemMetadata = new FieldMetadata($childName);
                $type->addItem($itemMetadata);
            }

            if (!$isGlobalBlock) {
                $itemSchemaMetadata = new SchemaMetadata([
                    new SchemaPropertyMetadata('type', false),
                ]);
                $this->schemaMetadataProvider->getMetadata($type->getItems())->willReturn($itemSchemaMetadata);
            }
            $metadata->addType($type);
        }

        $result = $this->imageMapPropertyMetadataMapper->mapPropertyMetadata($metadata);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'hotspots' => [
                    'type' => 'array',
                    'items' => [
                        'allOf' => [
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'headline',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    '$ref' => '#/definitions/headline',
                                ],
                            ],
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'text',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'required' => ['imageId', 'hotspots'],
        ], $result->toJsonSchema());
    }

    public function testMapPropertyMetatadaWithRequiredFalse(): void
    {
        $types = [
            'headline' => [
                'isGlobalBlock' => true,
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $metadata = new FieldMetadata('imageMap');
        $metadata->setRequired(false);
        foreach ($types as $key => $config) {
            $type = new FormMetadata();
            $type->setKey($key);

            $isGlobalBlock = $config['isGlobalBlock'] ?? false;
            if ($isGlobalBlock) {
                $tagMetadata = new TagMetadata();
                $tagMetadata->setName('sulu.global_block');
                $tagMetadata->setAttributes([
                    'global_block' => $key,
                ]);
                $type->addTag($tagMetadata);
            }

            foreach ($config['children'] ?? [] as $childName => $childType) {
                $itemMetadata = new FieldMetadata($childName);
                $type->addItem($itemMetadata);
            }

            if (!$isGlobalBlock) {
                $itemSchemaMetadata = new SchemaMetadata([
                    new SchemaPropertyMetadata('type', false),
                ]);
                $this->schemaMetadataProvider->getMetadata($type->getItems())->willReturn($itemSchemaMetadata);
            }
            $metadata->addType($type);
        }

        $result = $this->imageMapPropertyMetadataMapper->mapPropertyMetadata($metadata);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'hotspots' => [
                    'type' => 'array',
                    'items' => [
                        'allOf' => [
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'headline',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    '$ref' => '#/definitions/headline',
                                ],
                            ],
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'text',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $result->toJsonSchema());
    }
}
