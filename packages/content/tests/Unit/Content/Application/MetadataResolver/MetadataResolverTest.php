<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\MetadataResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;

class MetadataResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolve(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator(['default' => new DefaultPropertyResolver()])
        );
        $metadataResolver = new MetadataResolver($propertyResolverProvider);

        $sectionMetadata = new SectionMetadata('section1');
        $fieldMetadata1 = new FieldMetadata('field1');
        $fieldMetadata1->setType('text_line');
        $sectionMetadata->addItem($fieldMetadata1);
        $locale = 'en';

        $fieldMetadata2 = new FieldMetadata('field2');
        $fieldMetadata2->setType('text_line');
        $items = [
            'section1' => $sectionMetadata,
            'field2' => $fieldMetadata2,
        ];
        $data = [
            'field1' => 'value1',
            'field2' => 'value2',
        ];

        $result = $metadataResolver->resolveItems($items, $data, $locale);
        self::assertCount(2, $result);
        self::assertArrayHasKey('field1', $result);
        self::assertArrayHasKey('field2', $result);
        self::assertSame('value1', $result['field1']->getContent());
        self::assertSame('value2', $result['field2']->getContent());
    }

    public function testResolveNestedProperty(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator(['default' => new DefaultPropertyResolver()])
        );
        $metadataResolver = new MetadataResolver($propertyResolverProvider);

        $fieldMetadata = new FieldMetadata('nestedProperty/value');
        $fieldMetadata->setType('text_line');

        $result = $metadataResolver->resolveItems(
            ['nestedProperty/value' => $fieldMetadata],
            ['nestedProperty' => ['value' => 'Nested Value']],
            'en',
        );

        self::assertCount(1, $result);
        self::assertArrayHasKey('nestedProperty', $result);
        self::assertSame(['value' => 'Nested Value'], $result['nestedProperty']->getContent());
    }

    public function testResolveMultipleNestedProperties(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator(['default' => new DefaultPropertyResolver()])
        );
        $metadataResolver = new MetadataResolver($propertyResolverProvider);

        $fieldMetadata1 = new FieldMetadata('nestedProperty/value_1');
        $fieldMetadata1->setType('text_line');

        $fieldMetadata2 = new FieldMetadata('nestedProperty/value_2');
        $fieldMetadata2->setType('text_line');

        $result = $metadataResolver->resolveItems(
            [
                'nestedProperty/value_1' => $fieldMetadata1,
                'nestedProperty/value_2' => $fieldMetadata2,
            ],
            [
                'nestedProperty' => [
                    'value_1' => 'Nested Value 1',
                    'value_2' => 'Nested Value 2',
                ],
            ],
            'en',
        );

        self::assertCount(1, $result);
        self::assertArrayHasKey('nestedProperty', $result);

        $nestedProperty = $result['nestedProperty']->getContent();
        self::assertIsArray($nestedProperty);
        self::assertArrayHasKey('value_1', $nestedProperty);
        self::assertArrayHasKey('value_2', $nestedProperty);
        self::assertSame('Nested Value 1', $nestedProperty['value_1']);
        self::assertSame('Nested Value 2', $nestedProperty['value_2']);
    }

    public function testResolveFlatSlashPathStaysFlatWhenDataIsFlat(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator(['default' => new DefaultPropertyResolver()])
        );
        $metadataResolver = new MetadataResolver($propertyResolverProvider);

        $fieldMetadata = new FieldMetadata('seo/title');
        $fieldMetadata->setType('text_line');

        $result = $metadataResolver->resolveItems(
            ['seo/title' => $fieldMetadata],
            ['seo/title' => 'Seo Title'],
            'en',
        );

        self::assertCount(1, $result);
        self::assertArrayHasKey('seo/title', $result);
        self::assertSame('Seo Title', $result['seo/title']->getContent());
    }

    public function testResolveFlatSlashPathsDoNotMergeRootForMissingSiblingValues(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator(['default' => new DefaultPropertyResolver()])
        );
        $metadataResolver = new MetadataResolver($propertyResolverProvider);

        $fieldMetadata1 = new FieldMetadata('excerpt/title');
        $fieldMetadata1->setType('text_line');

        $fieldMetadata2 = new FieldMetadata('excerpt/description');
        $fieldMetadata2->setType('text_line');

        $result = $metadataResolver->resolveItems(
            [
                'excerpt/title' => $fieldMetadata1,
                'excerpt/description' => $fieldMetadata2,
            ],
            ['excerpt/title' => 'Excerpt Title'],
            'en',
        );

        self::assertCount(2, $result);
        self::assertArrayHasKey('excerpt/title', $result);
        self::assertArrayHasKey('excerpt/description', $result);
        self::assertArrayNotHasKey('excerpt', $result);
        self::assertSame('Excerpt Title', $result['excerpt/title']->getContent());
        self::assertNull($result['excerpt/description']->getContent());
    }
}
