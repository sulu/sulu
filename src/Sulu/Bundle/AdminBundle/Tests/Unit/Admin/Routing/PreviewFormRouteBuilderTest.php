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
use Sulu\Bundle\AdminBundle\Admin\Routing\PreviewFormRouteBuilder;

class PreviewFormRouteBuilderTest extends TestCase
{
    public function testBuildPreviewFormRouteWithClone()
    {
        $routeBuilder = (new PreviewFormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles');

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function testBuildPreviewFormRouteWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $route = (new PreviewFormRouteBuilder('sulu_category.edit_form.details', '/details'))
            ->getRoute();
    }

    public function provideBuildPreviewFormRoute()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'categories',
                'Details',
                'name == "Test"',
                100,
                512,
                'sulu_category.edit_form',
                'sulu_category.list',
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                'tags',
                null,
                null,
                null,
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideBuildPreviewFormRoute
     */
    public function testBuildPreviewFormRoute(
        string $name,
        string $path,
        string $resourceKey,
        string $formKey,
        ?string $tabTitle,
        ?string $tabCondition,
        ?string $tabOrder,
        ?string $tabPriority,
        ?string $editRoute,
        ?string $backRoute
    ) {
        $routeBuilder = (new PreviewFormRouteBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setFormKey($formKey);

        if ($tabTitle) {
            $routeBuilder->setTabTitle($tabTitle);
        }

        if ($tabCondition) {
            $routeBuilder->setTabCondition($tabCondition);
        }

        if ($tabOrder) {
            $routeBuilder->setTabOrder($tabOrder);
        }

        if ($tabPriority) {
            $routeBuilder->setTabPriority($tabPriority);
        }

        if ($editRoute) {
            $routeBuilder->setEditRoute($editRoute);
        }

        if ($backRoute) {
            $routeBuilder->setBackRoute($backRoute);
        }

        $route = $routeBuilder->getRoute();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($formKey, $route->getOption('formKey'));
        $this->assertEquals($tabTitle, $route->getOption('tabTitle'));
        $this->assertEquals($tabCondition, $route->getOption('tabCondition'));
        $this->assertEquals($tabOrder, $route->getOption('tabOrder'));
        $this->assertEquals($tabPriority, $route->getOption('tabPriority'));
        $this->assertEquals($editRoute, $route->getOption('editRoute'));
        $this->assertEquals($backRoute, $route->getOption('backRoute'));
        $this->assertNull($route->getParent());
        $this->assertEquals('sulu_admin.preview_form', $route->getView());
    }

    public function testBuildFormWithToolbarActions()
    {
        $route = (new PreviewFormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addToolbarActions(['sulu_admin.save', 'sulu_admin.types'])
            ->addToolbarActions(['sulu_admin.delete'])
            ->getRoute();

        $this->assertEquals(
            ['sulu_admin.save', 'sulu_admin.types', 'sulu_admin.delete'],
            $route->getOption('toolbarActions')
        );
    }

    public function testBuildFormWithRouterAttributesToFormStore()
    {
        $route = (new PreviewFormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToFormStore(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToFormStore(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToFormStore')
        );
    }

    public function testBuildFormWithRouterAttributesToEditRoute()
    {
        $route = (new PreviewFormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToEditRoute(['webspace', 'parent'])
            ->addRouterAttributesToEditRoute(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace', 'parent', 'locale'],
            $route->getOption('routerAttributesToEditRoute')
        );
    }

    public function testBuildFormWithIdQueryParameter()
    {
        $route = (new PreviewFormRouteBuilder('sulu_security.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setIdQueryParameter('contactId')
            ->getRoute();

        $this->assertEquals(
            'contactId',
            $route->getOption('idQueryParameter')
        );
    }

    public function testBuildFormWithPreviewCondition()
    {
        $route = (new PreviewFormRouteBuilder('sulu_page.page_edit_form.details', '/pages/:id/details'))
            ->setResourceKey('pages')
            ->setFormKey('pages')
            ->setPreviewCondition('nodeType == 1')
            ->getRoute();

        $this->assertEquals('nodeType == 1', $route->getOption('previewCondition'));
    }

    public function testBuildFormWithLocales()
    {
        $route = (new PreviewFormRouteBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getRoute();

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
    }

    public function testBuildFormWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new PreviewFormRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getRoute();
    }

    public function testBuildFormWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new PreviewFormRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->getRoute();
    }
}
