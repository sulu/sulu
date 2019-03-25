<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\Routing;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Routing\FormOverlayListRouteBuilder;

class FormOverlayListRouteBuilderTest extends TestCase
{
    public function testBuildFormOverlayListRouteWithClone()
    {
        $routeBuilder = (new FormOverlayListRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table']);

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function testBuildFormOverlayListRouteWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $route = (new FormOverlayListRouteBuilder('sulu_category.list', '/category'))
            ->getRoute();
    }

    public function testBuildFormOverlayListRouteWithoutListAdapters()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"addListAdapters"/');

        $route = (new FormOverlayListRouteBuilder('sulu_category.list', '/category'))
            ->setResourceKey('categories')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->getRoute();
    }

    public function provideBuildFormOverlayListRoute()
    {
        return [
            [
                'sulu_category.list',
                '/categories',
                'categories',
                'categories',
                'category_details',
                'Categories',
                ['table', 'column_list'],
                'sulu_category.add_category',
                'sulu_category.edit_category',
                'small',
            ],
            [
                'sulu_tag.list',
                '/tags',
                'tags',
                'other_tags',
                'tag_details',
                'Tags',
                ['table'],
                'sulu_tag.add_tag',
                'sulu_tag.edit_tag',
                'large',
            ],
        ];
    }

    /**
     * @dataProvider provideBuildFormOverlayListRoute
     */
    public function testBuildFormOverlayListRoute(
        string $name,
        string $path,
        string $resourceKey,
        string $listKey,
        string $formKey,
        string $title,
        array $listAdapters,
        string $addOverlayTitle,
        string $editOverlayTitle,
        string $overlaySize
    ) {
        $route = (new FormOverlayListRouteBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setListKey($listKey)
            ->setFormKey($formKey)
            ->setTitle($title)
            ->addListAdapters($listAdapters)
            ->setAddOverlayTitle($addOverlayTitle)
            ->setEditOverlayTitle($editOverlayTitle)
            ->setOverlaySize($overlaySize)
            ->getRoute();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($listKey, $route->getOption('listKey'));
        $this->assertEquals($formKey, $route->getOption('formKey'));
        $this->assertEquals($title, $route->getOption('title'));
        $this->assertEquals($listAdapters, $route->getOption('adapters'));
        $this->assertEquals($addOverlayTitle, $route->getOption('addOverlayTitle'));
        $this->assertEquals($editOverlayTitle, $route->getOption('editOverlayTitle'));
        $this->assertEquals($overlaySize, $route->getOption('overlaySize'));
        $this->assertEquals('sulu_admin.form_overlay_list', $route->getView());
    }

    public function testBuildFormOverlayListRouteAddingAdaptersTwice()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table', 'column_list'])
            ->addListAdapters(['tree'])
            ->getRoute();

        $this->assertEquals(['table', 'column_list', 'tree'], $route->getOption('adapters'));
    }

    public function testBuildListWithLocales()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getRoute();

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
        $this->assertEquals('de', $route->getAttributeDefault('locale'));
    }

    public function testBuildListWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getRoute();
    }

    public function testBuildListWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table'])
            ->getRoute();
    }

    public function testBuildListWithoutListKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('"listKey"');

        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->addListAdapters(['table'])
            ->getRoute();
    }

    public function testBuildListWithoutFormKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('"formKey"');

        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->getRoute();
    }

    public function testBuildFormOverlayListRouteWithSearch()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->disableSearching()
            ->enableSearching()
            ->getRoute();

        $this->assertTrue($route->getOption('searchable'));
    }

    public function testBuildFormOverlayListRouteWithoutSearch()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->enableSearching()
            ->disableSearching()
            ->getRoute();

        $this->assertFalse($route->getOption('searchable'));
    }

    public function testBuildListWithRouterAttributesToListStore()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListStore(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToListStore(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToListStore')
        );
    }

    public function testBuildFormWithRouterAttributesToFormStore()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToFormStore(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToFormStore(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToFormStore')
        );
    }

    public function testBuildWithResourceStorePropertiesToListStore()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addResourceStorePropertiesToListStore(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addResourceStorePropertiesToListStore(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('resourceStorePropertiesToListStore')
        );
    }

    public function testBuildListSetParent()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->getRoute();

        $this->assertEquals('sulu_role.parent_view', $route->getParent());
    }

    public function testBuildListSetTabTitle()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setTabTitle('sulu_role.title')
            ->getRoute();

        $this->assertEquals('sulu_role.title', $route->getOption('tabTitle'));
    }

    public function testBuildListSetTabOrder()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setTabOrder(5)
            ->getRoute();

        $this->assertEquals(5, $route->getOption('tabOrder'));
    }

    public function testBuildListSetTabCondition()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setTabCondition('state == 1')
            ->getRoute();

        $this->assertEquals('state == 1', $route->getOption('tabCondition'));
    }

    public function testBuildListSetBackRoute()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setBackRoute('sulu_category.edit_form')
            ->getRoute();

        $this->assertEquals('sulu_category.edit_form', $route->getOption('backRoute'));
    }

    public function testBuildAddToolbarActions()
    {
        $route = (new FormOverlayListRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addToolbarActions(['sulu_admin.add', 'sulu_admin.move'])
            ->addToolbarActions(['sulu_admin.delete'])
            ->getRoute();

        $this->assertEquals(['sulu_admin.add', 'sulu_admin.move', 'sulu_admin.delete'], $route->getOption('toolbarActions'));
    }
}
