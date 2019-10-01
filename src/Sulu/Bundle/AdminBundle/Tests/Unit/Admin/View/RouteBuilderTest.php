<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\Route;
use Sulu\Bundle\AdminBundle\Admin\View\RouteBuilder;

class RouteBuilderTest extends TestCase
{
    public function provideBuildRoute()
    {
        return [
            [
                'sulu_tag.add_form.details',
                '/tags/add',
                'sulu_admin.form',
                ['test1' => 'value1'],
                ['attribute1' => 'defaultValue1'],
                'sulu_tag.add_form',
                ['tag'],
            ],
            [
                'sulu_tag.list',
                '/tags',
                'sulu_admin.list',
                ['test2' => 'value2', 'test3' => 'value3'],
                ['attribute2' => 'defaultValue2', 'attribute3' => 'defaultValue3'],
                null,
                ['webspace'],
            ],
        ];
    }

    /**
     * @dataProvider provideBuildRoute
     */
    public function testBuildTabRoute(
        string $name,
        string $path,
        string $view,
        array $options,
        array $attributeDefaults,
        ?string $parent,
        array $rerenderAttributes
    ) {
        $routeBuilder = new RouteBuilder($name, $path, $view);
        $expectedRoute = new Route($name, $path, $view);

        foreach ($options as $optionKey => $optionValue) {
            $routeBuilder->setOption($optionKey, $optionValue);
            $expectedRoute->setOption($optionKey, $optionValue);
        }

        foreach ($attributeDefaults as $attributeDefaultKey => $attributeDefaultValue) {
            $routeBuilder->setAttributeDefault($attributeDefaultKey, $attributeDefaultValue);
            $expectedRoute->setAttributeDefault($attributeDefaultKey, $attributeDefaultValue);
        }

        if ($parent) {
            $routeBuilder->setParent($parent);
            $expectedRoute->setParent($parent);
        }

        foreach ($rerenderAttributes as $rerenderAttribute) {
            $routeBuilder->addRerenderAttribute($rerenderAttribute);
            $expectedRoute->addRerenderAttribute($rerenderAttribute);
        }

        $this->assertEquals($expectedRoute, $routeBuilder->getRoute());
    }

    public function testOverrideView()
    {
        $routeBuilder = new RouteBuilder('sulu_admin.test', '/test', 'test1');
        $routeBuilder->setView('test2');

        $expectedRoute = new Route('sulu_admin.test', '/test', 'test2');

        $this->assertEquals($expectedRoute, $routeBuilder->getRoute());
    }

    public function testGetName()
    {
        $routeBuilder = new RouteBuilder('sulu_admin.test', '/test', 'test1');

        $this->assertEquals('sulu_admin.test', $routeBuilder->getName());
    }
}
