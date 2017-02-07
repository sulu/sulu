<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Tests\Unit;

use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\Builder;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function provideBoolean()
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider provideBoolean
     */
    public function testTags($enable)
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableTags($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasTags());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    /**
     * @dataProvider provideBoolean
     */
    public function testCategories($enable)
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableCategories($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasCategories());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    /**
     * @dataProvider provideBoolean
     */
    public function testLimit($enable)
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableLimit($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasLimit());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    /**
     * @dataProvider provideBoolean
     */
    public function testPresentAs($enable)
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enablePresentAs($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasPresentAs());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    /**
     * @dataProvider provideBoolean
     */
    public function testPagination($enable)
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enablePagination($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasPagination());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPresentAs());
    }

    public function testSorting()
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

        $this->assertEquals($builder, $builder->enableSorting($sorting));

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

    public function testDatasource()
    {
        $component = 'test@sulutest';
        $options = ['url' => '/admin/api/test'];

        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableDatasource($component, $options));

        $configuration = $builder->getConfiguration();

        $this->assertTrue($configuration->hasDatasource());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasPagination());

        $componentConfiguration = $configuration->getDatasource();

        $this->assertEquals($component, $componentConfiguration->getName());
        $this->assertEquals($options, $componentConfiguration->getOptions());
    }

    public function testDatasourceWithoutOptions()
    {
        $component = 'test@sulutest';

        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableDatasource($component));

        $configuration = $builder->getConfiguration();

        $this->assertTrue($configuration->hasDatasource());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasPagination());

        $componentConfiguration = $configuration->getDatasource();

        $this->assertEquals($component, $componentConfiguration->getName());
        $this->assertEquals([], $componentConfiguration->getOptions());
    }
}
