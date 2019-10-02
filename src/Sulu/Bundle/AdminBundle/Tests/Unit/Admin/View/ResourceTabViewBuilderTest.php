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
use Sulu\Bundle\AdminBundle\Admin\View\ResourceTabViewBuilder;

class ResourceTabViewBuilderTest extends TestCase
{
    public function testBuildResourceTabViewWithClone()
    {
        $viewBuilder = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles');

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function testBuildResourceTabViewWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $view = (new ResourceTabViewBuilder('sulu_category.list', '/category'))
            ->getView();
    }

    public function provideBuildResourceTabView()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'sulu_category.list',
                null,
                ['webspace'],
                'title',
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                null,
                null,
                ['sortColumn', 'sortOrder'],
                null,
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'sulu_category.list',
                ['webspace'],
                null,
                'title',
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'sulu_category.list',
                ['webspace', 'active' => 'id'],
                ['sortColumn', 'sortOrder'],
                'title',
            ],
        ];
    }

    /**
     * @dataProvider provideBuildResourceTabView
     */
    public function testBuildResourceTabView(
        string $name,
        string $path,
        string $resourceKey,
        ?string $backView,
        ?array $routerAttributesToBackView,
        ?array $routerAttributesToBlacklist,
        ?string $titleProperty
    ) {
        $viewBuilder = (new ResourceTabViewBuilder($name, $path))
            ->setResourceKey($resourceKey);

        if ($backView) {
            $viewBuilder->setBackView($backView);
        }

        if ($routerAttributesToBackView) {
            $viewBuilder->addRouterAttributesToBackView($routerAttributesToBackView);
        }

        if ($titleProperty) {
            $viewBuilder->setTitleProperty($titleProperty);
        }

        $view = $viewBuilder->getView();

        $this->assertSame($name, $view->getName());
        $this->assertSame($path, $view->getPath());
        $this->assertSame($resourceKey, $view->getOption('resourceKey'));
        $this->assertSame($backView, $view->getOption('backView'));
        $this->assertSame($routerAttributesToBackView, $view->getOption('routerAttributesToBackView'));
        $this->assertSame($titleProperty, $view->getOption('titleProperty'));
        $this->assertSame('sulu_admin.resource_tabs', $view->getType());
    }

    public function testBuildFormWithParent()
    {
        $view = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption()
    {
        $view = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }

    public function testBuildResourceTabWithLocales()
    {
        $view = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();

        $this->assertSame(['de', 'en', 'nl', 'fr'], $view->getOption('locales'));
    }

    public function testBuildResourceTabWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $view = (new ResourceTabViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();
    }

    public function testBuildResourceTabWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $view = (new ResourceTabViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->getView();
    }
}
