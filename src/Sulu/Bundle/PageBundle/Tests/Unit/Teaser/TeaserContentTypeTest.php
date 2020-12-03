<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Teaser;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Bundle\PageBundle\Teaser\Teaser;
use Sulu\Bundle\PageBundle\Teaser\TeaserContentType;
use Sulu\Bundle\PageBundle\Teaser\TeaserManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreNotExistsException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;

class TeaserContentTypeTest extends TestCase
{
    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @var ReferenceStorePoolInterface
     */
    private $referenceStorePool;

    /**
     * @var ReferenceStoreInterface
     */
    private $mediaReferenceStore;

    /**
     * @var TeaserContentType
     */
    private $contentType;

    protected function setUp(): void
    {
        $this->teaserProviderPool = $this->prophesize(TeaserProviderPoolInterface::class);
        $this->teaserManager = $this->prophesize(TeaserManagerInterface::class);
        $this->referenceStorePool = $this->prophesize(ReferenceStorePoolInterface::class);
        $this->mediaReferenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->referenceStorePool->getStore('media')->willReturn($this->mediaReferenceStore->reveal());

        $this->contentType = new TeaserContentType(
            $this->teaserProviderPool->reveal(),
            $this->teaserManager->reveal(),
            $this->referenceStorePool->reveal(),
            new PropertyMetadataMinMaxValueResolver()
        );
    }

    public function testGetDefaultParameter()
    {
        $configuration = [new TeaserConfiguration('content', 'pages', 'column_list', ['title'], 'Choose')];
        $this->teaserProviderPool->getConfiguration()->willReturn($configuration);

        $this->assertEquals(
            [
                'providerConfiguration' => $configuration,
                'present_as' => new PropertyParameter('present_as', [], 'collection'),
            ],
            $this->contentType->getDefaultParams()
        );
    }

    public function testGetContentDataEmpty()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);

        $this->assertEquals([], $this->contentType->getContentData($property->reveal()));
    }

    public function testGetContentData()
    {
        $items = [
            ['type' => 'content', 'id' => '123-123-123', 'mediaId' => 15],
            ['type' => 'media', 'id' => 1, 'mediaId' => null],
        ];

        $teasers = \array_map(
            function($item) {
                $teaser = $this->prophesize(Teaser::class);
                $teaser->getType()->willReturn($item['type']);
                $teaser->getId()->willReturn($item['id']);
                $teaser->getMediaId()->willReturn($item['mediaId']);

                return $teaser->reveal();
            },
            $items
        );

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['items' => $items]);
        $property->getStructure()->willReturn($structure);

        $this->mediaReferenceStore->add(15);

        $this->teaserManager->find($items, 'de')->shouldBeCalled()->willReturn($teasers);

        $this->assertEquals($teasers, $this->contentType->getContentData($property->reveal()));
    }

    public function testGetViewDataEmpty()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['presentAs' => 'col1']);

        $this->assertEquals(
            ['items' => [], 'presentAs' => 'col1'],
            $this->contentType->getViewData($property->reveal())
        );
    }

    public function testGetViewData()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);

        $this->assertEquals(
            ['items' => [], 'presentAs' => null],
            $this->contentType->getViewData($property->reveal())
        );
    }

    public function testPreResolve()
    {
        $data = [
            'items' => [
                ['type' => 'article', 'id' => 1],
                ['type' => 'test', 'id' => 2],
                ['type' => 'content', 'id' => 3],
            ],
        ];

        $articleStore = $this->prophesize(ReferenceStoreInterface::class);
        $contentStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->referenceStorePool->getStore('article')->willReturn($articleStore->reveal());
        $this->referenceStorePool->getStore('content')->willReturn($contentStore->reveal());
        $this->referenceStorePool->getStore('test')
            ->willThrow(
                new ReferenceStoreNotExistsException('test', ['article', 'content'])
            );

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn($data);

        $this->contentType->preResolve($property->reveal());

        $articleStore->add(1)->shouldBeCalled();
        $contentStore->add(3)->shouldBeCalled();
    }

    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    private function getEmptyArraySchema(): array
    {
        return [
            'type' => 'array',
            'items' => ['required' => []],
            'maxItems' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'string',
                                                'name' => 'id',
                                            ],
                                            'type' => [
                                                'type' => 'string',
                                                'name' => 'type',
                                            ],
                                            'title' => [
                                                'type' => 'string',
                                                'name' => 'title',
                                            ],
                                            'description' => [
                                                'type' => 'string',
                                                'name' => 'description',
                                            ],
                                            'mediaId' => [
                                                'type' => 'number',
                                                'name' => 'mediaId',
                                            ],
                                        ],
                                        'required' => ['id', 'type'],
                                    ],
                                    'uniqueItems' => true,
                                ],
                            ],
                            'name' => 'items',
                        ],
                        'presentAs' => [
                            'type' => 'string',
                            'name' => 'presentAs',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                            'name' => 'id',
                        ],
                        'type' => [
                            'type' => 'string',
                            'name' => 'type',
                        ],
                        'title' => [
                            'type' => 'string',
                            'name' => 'title',
                        ],
                        'description' => [
                            'type' => 'string',
                            'name' => 'description',
                        ],
                        'mediaId' => [
                            'type' => 'number',
                            'name' => 'mediaId',
                        ],
                    ],
                    'required' => ['id', 'type'],
                ],
                'presentAs' => [
                    'type' => 'string',
                    'name' => 'presentAs',
                ],
            ],
            'required' => ['ids'],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMax(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 3],
        ]);

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'string',
                                                'name' => 'id',
                                            ],
                                            'type' => [
                                                'type' => 'string',
                                                'name' => 'type',
                                            ],
                                            'title' => [
                                                'type' => 'string',
                                                'name' => 'title',
                                            ],
                                            'description' => [
                                                'type' => 'string',
                                                'name' => 'description',
                                            ],
                                            'mediaId' => [
                                                'type' => 'number',
                                                'name' => 'mediaId',
                                            ],
                                        ],
                                        'required' => ['id', 'type'],
                                    ],
                                    'minItems' => 2,
                                    'maxItems' => 3,
                                    'uniqueItems' => true,
                                ],
                            ],
                            'name' => 'items',
                        ],
                        'presentAs' => [
                            'type' => 'string',
                            'name' => 'presentAs',
                        ],
                    ],
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

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'string',
                                                'name' => 'id',
                                            ],
                                            'type' => [
                                                'type' => 'string',
                                                'name' => 'type',
                                            ],
                                            'title' => [
                                                'type' => 'string',
                                                'name' => 'title',
                                            ],
                                            'description' => [
                                                'type' => 'string',
                                                'name' => 'description',
                                            ],
                                            'mediaId' => [
                                                'type' => 'number',
                                                'name' => 'mediaId',
                                            ],
                                        ],
                                        'required' => ['id', 'type'],
                                    ],
                                    'minItems' => 2,
                                    'uniqueItems' => true,
                                ],
                            ],
                            'name' => 'items',
                        ],
                        'presentAs' => [
                            'type' => 'string',
                            'name' => 'presentAs',
                        ],
                    ],
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

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'string',
                                                'name' => 'id',
                                            ],
                                            'type' => [
                                                'type' => 'string',
                                                'name' => 'type',
                                            ],
                                            'title' => [
                                                'type' => 'string',
                                                'name' => 'title',
                                            ],
                                            'description' => [
                                                'type' => 'string',
                                                'name' => 'description',
                                            ],
                                            'mediaId' => [
                                                'type' => 'number',
                                                'name' => 'mediaId',
                                            ],
                                        ],
                                        'required' => ['id', 'type'],
                                    ],
                                    'maxItems' => 2,
                                    'uniqueItems' => true,
                                ],
                            ],
                            'name' => 'items',
                        ],
                        'presentAs' => [
                            'type' => 'string',
                            'name' => 'presentAs',
                        ],
                    ],
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

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'string',
                                                'name' => 'id',
                                            ],
                                            'type' => [
                                                'type' => 'string',
                                                'name' => 'type',
                                            ],
                                            'title' => [
                                                'type' => 'string',
                                                'name' => 'title',
                                            ],
                                            'description' => [
                                                'type' => 'string',
                                                'name' => 'description',
                                            ],
                                            'mediaId' => [
                                                'type' => 'number',
                                                'name' => 'mediaId',
                                            ],
                                        ],
                                        'required' => ['id', 'type'],
                                    ],
                                    'minItems' => 2,
                                    'maxItems' => 3,
                                    'uniqueItems' => true,
                                ],
                            ],
                            'name' => 'items',
                        ],
                        'presentAs' => [
                            'type' => 'string',
                            'name' => 'presentAs',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 'invalid-value'],
        ]);

        $this->contentType->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be greater than or equal "0"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => -1],
        ]);

        $this->contentType->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 0],
        ]);

        $this->contentType->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 'invalid-value'],
        ]);

        $this->contentType->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 0],
        ]);

        $this->contentType->mapPropertyMetadata($propertyMetadata);
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

        $this->contentType->mapPropertyMetadata($propertyMetadata);
    }
}
