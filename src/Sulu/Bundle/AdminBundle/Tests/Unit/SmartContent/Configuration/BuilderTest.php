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

namespace Sulu\Bundle\AdminBundle\SmartContent\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Compat\PropertyParameter;

class BuilderTest extends TestCase
{
    /**
     * @return array<array<bool>>
     */
    public static function provideBoolean(): array
    {
        return [[true], [false]];
    }

    #[DataProvider('provideBoolean')]
    public function testTags(bool $enable): void
    {
        $builder = Builder::create();

        $this->assertSame($builder, $builder->enableTags($enable));

        $configuration = $builder->getConfiguration();

        $this->assertSame($enable, $configuration->hasTags());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[DataProvider('provideBoolean')]
    public function testCategories(bool $enable): void
    {
        $builder = Builder::create();

        $this->assertSame($builder, $builder->enableCategories($enable));

        $configuration = $builder->getConfiguration();

        $this->assertSame($enable, $configuration->hasCategories());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[DataProvider('provideBoolean')]
    public function testLimit(bool $enable): void
    {
        $builder = Builder::create();

        $this->assertSame($builder, $builder->enableLimit($enable));

        $configuration = $builder->getConfiguration();

        $this->assertSame($enable, $configuration->hasLimit());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[DataProvider('provideBoolean')]
    public function testPresentAs(bool $enable): void
    {
        $builder = Builder::create();

        $this->assertSame($builder, $builder->enablePresentAs($enable));

        $configuration = $builder->getConfiguration();

        $this->assertSame($enable, $configuration->hasPresentAs());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[DataProvider('provideBoolean')]
    public function testPagination(bool $enable): void
    {
        $builder = Builder::create();

        $this->assertSame($builder, $builder->enablePagination($enable));

        $configuration = $builder->getConfiguration();

        $this->assertSame($enable, $configuration->hasPagination());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPresentAs());
    }

    public function testSorting(): void
    {
        $expectedSorting = [
            new PropertyParameter('entity.id', 'Identification'),
            new PropertyParameter('entity.name', 'Name'),
            new PropertyParameter('entity.test', 'Test'),
        ];
        $sorting = [
            ['column' => 'entity.id', 'title' => 'Identification'],
            ['column' => 'entity.name', 'title' => 'Name'],
            ['column' => 'entity.test', 'title' => 'Test'],
        ];

        $builder = Builder::create();

        $this->assertSame($builder, $builder->enableSorting($sorting));

        $configuration = $builder->getConfiguration();

        $this->assertTrue($configuration->hasSorting());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasPagination());

        $this->assertEquals($expectedSorting, $configuration->getSorting());
    }

    public function testDatasource(): void
    {
        $builder = Builder::create();

        $this->assertSame($builder, $builder->enableDatasource('collections', 'collections', 'column_list'));

        $configuration = $builder->getConfiguration();

        $this->assertTrue($configuration->hasDatasource());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasPagination());

        $this->assertSame('collections', $configuration->getDatasourceResourceKey());
        $this->assertSame('collections', $configuration->getDatasourceListKey());
        $this->assertSame('column_list', $configuration->getDatasourceAdapter());
    }

    public function testView(): void
    {
        $builder = Builder::create();

        $this->assertSame($builder, $builder->enableView('edit_form', ['id' => 'id']));

        $configuration = $builder->getConfiguration();

        $this->assertSame('edit_form', $configuration->getView());
        $this->assertSame(['id' => 'id'], $configuration->getResultToView());
    }
}
