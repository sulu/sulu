<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\SmartContent\Orm;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Prophecy\Argument;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\ResourceItemInterface;

class BaseDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDefaultPropertyParameter()
    {
        $repository = $this->prophesize(DataProviderRepositoryInterface::class);
        $serializer = $this->prophesize(SerializerInterface::class);

        /** @var BaseDataProvider $provider */
        $provider = $this->getMockForAbstractClass(
            BaseDataProvider::class,
            [$repository->reveal(), $serializer->reveal()]
        );

        $this->assertEquals([], $provider->getDefaultPropertyParameter());
    }

    public function configurationProvider()
    {
        return [
            [true, true, true, true, true, []],
            [true, false, true, true, true, []],
            [true, true, false, true, true, []],
            [true, true, true, false, true, []],
            [true, true, true, true, false, []],
            [true, true, true, true, true, ['asdf', 'qwertz']],
        ];
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testInitConfiguration($tags, $categories, $limit, $presentAs, $paginated, $sorting)
    {
        $repository = $this->prophesize(DataProviderRepositoryInterface::class);
        $serializer = $this->prophesize(SerializerInterface::class);

        /** @var BaseDataProvider $provider */
        $provider = $this->getMockForAbstractClass(
            BaseDataProvider::class,
            [$repository->reveal(), $serializer->reveal()]
        );

        $class = new \ReflectionClass(BaseDataProvider::class);
        $method = $class->getMethod('initConfiguration');
        $method->setAccessible(true);

        /** @var ProviderConfigurationInterface $configuration */
        $configuration = $method->invokeArgs(
            $provider,
            [
                $tags,
                $categories,
                $limit,
                $presentAs,
                $paginated,
                $sorting,
            ]
        );

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
        $this->assertEquals(false, $configuration->hasDatasource());
        $this->assertEquals($tags, $configuration->hasTags());
        $this->assertEquals($categories, $configuration->hasCategories());
        $this->assertEquals($limit, $configuration->hasLimit());
        $this->assertEquals($presentAs, $configuration->hasPresentAs());
        $this->assertEquals($sorting, $configuration->getSorting());
        $this->assertEquals($paginated, $configuration->hasPagination());
        $this->assertEquals(count($sorting) > 0, $configuration->hasSorting());
    }

    public function testResolveDataSource()
    {
        $repository = $this->prophesize(DataProviderRepositoryInterface::class);
        $serializer = $this->prophesize(SerializerInterface::class);

        /** @var BaseDataProvider $provider */
        $provider = $this->getMockForAbstractClass(
            BaseDataProvider::class,
            [$repository->reveal(), $serializer->reveal()]
        );

        $this->assertNull($provider->resolveDatasource('', [], []));
    }

    public function filtersProvider()
    {
        return [
            [
                [],
                null,
                1,
                null,
                [],
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                false,
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            ],
            [
                [],
                null,
                1,
                null,
                ['test' => 1],
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                false,
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            ],
            [
                ['tags' => []],
                null,
                1,
                null,
                [],
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                false,
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            ],
            [
                [],
                2,
                1,
                null,
                [],
                [['id' => 1], ['id' => 2]],
                false,
                [['id' => 1], ['id' => 2]],
            ],
            [
                [],
                null,
                1,
                2,
                [],
                [['id' => 1], ['id' => 2]],
                false,
                [['id' => 1], ['id' => 2]],
            ],
            [
                [],
                null,
                1,
                2,
                [],
                [['id' => 1], ['id' => 2], ['id' => 3]],
                true,
                [['id' => 1], ['id' => 2]],
            ],
            [
                [],
                null,
                2,
                2,
                [],
                [['id' => 2], ['id' => 3]],
                false,
                [['id' => 2], ['id' => 3]],
            ],
            [
                [],
                null,
                2,
                2,
                [],
                [['id' => 2], ['id' => 3], ['id' => 4]],
                true,
                [['id' => 2], ['id' => 3]],
            ],
        ];
    }

    /**
     * @dataProvider filtersProvider
     */
    public function testResolveFilters(
        array $filters,
        $limit,
        $page,
        $pageSize,
        $options,
        $repositoryResult,
        $hasNextPage,
        $items
    ) {
        $repository = $this->prophesize(DataProviderRepositoryInterface::class);
        $repository->findByFilters($filters, $page, $pageSize, $limit, 'en', $options)->shouldBeCalled()->willReturn(
            $repositoryResult
        );

        $serializer = $this->prophesize(SerializerInterface::class);

        /** @var BaseDataProvider $provider */
        $provider = $this->getMockForAbstractClass(
            BaseDataProvider::class,
            [$repository->reveal(), $serializer->reveal()]
        );

        $class = new \ReflectionClass(BaseDataProvider::class);
        $method = $class->getMethod('resolveFilters');
        $method->setAccessible(true);

        $result = $method->invokeArgs($provider, [$filters, 'en', $limit, $page, $pageSize, $options]);

        $this->assertEquals($items, $result[0]);
        $this->assertEquals($hasNextPage, $result[1]);
    }

    /**
     * @dataProvider filtersProvider
     */
    public function testResolveDataItems(
        array $filters,
        $limit,
        $page,
        $pageSize,
        $options,
        $repositoryResult,
        $hasNextPage,
        $items
    ) {
        $repository = $this->prophesize(DataProviderRepositoryInterface::class);
        $repository->findByFilters($filters, $page, $pageSize, $limit, 'en', [])->shouldBeCalled()->willReturn(
            $repositoryResult
        );

        $serializer = $this->prophesize(SerializerInterface::class);

        /** @var BaseDataProvider $provider */
        $provider = $this->getMockForAbstractClass(
            BaseDataProvider::class,
            [$repository->reveal(), $serializer->reveal()]
        );
        $provider->expects($this->any())->method('decorateDataItems')->willReturn($items);

        $result = $provider->resolveDataItems(
            $filters,
            [],
            ['locale' => 'en', 'webspace' => 'sulu_io'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
        $this->assertEquals([], $result->getReferencedUuids());
    }

    /**
     * @dataProvider filtersProvider
     */
    public function testResolveResourceItems(
        array $filters,
        $limit,
        $page,
        $pageSize,
        $options,
        $repositoryResult,
        $hasNextPage,
        $items
    ) {
        $mockedItems = array_map(
            function ($item) {
                $mock = $this->prophesize(ResourceItemInterface::class);
                $mock->getId()->willReturn($item['id']);

                return $mock->reveal();
            },
            $repositoryResult
        );

        $repository = $this->prophesize(DataProviderRepositoryInterface::class);
        $repository->findByFilters($filters, $page, $pageSize, $limit, 'en', [])->shouldBeCalled()->willReturn(
            $mockedItems
        );

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize(
            Argument::type(ResourceItemInterface::class),
            'array',
            Argument::type(SerializationContext::class)
        )->will(
            function ($args) {
                return ['id' => $args[0]->getId()];
            }
        );

        /** @var BaseDataProvider $provider */
        $provider = $this->getMockForAbstractClass(
            BaseDataProvider::class,
            [$repository->reveal(), $serializer->reveal()]
        );

        $result = $provider->resolveResourceItems(
            $filters,
            [],
            ['locale' => 'en', 'webspace' => 'sulu_io'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals([], $result->getReferencedUuids());
        $this->assertCount(count($items), $result->getItems());

        for ($i = 0, $len = count($items); $i < $len; ++$i) {
            $expected = $items[$i];
            $item = $result->getItems()[$i];

            $this->assertEquals($expected['id'], $item->getResource()->getId());
            $this->assertEquals($expected['id'], $item['id']);
        }
    }
}
