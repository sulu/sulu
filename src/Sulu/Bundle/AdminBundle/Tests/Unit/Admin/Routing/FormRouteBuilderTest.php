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
use Sulu\Bundle\AdminBundle\Admin\Routing\FormRouteBuilder;
use Sulu\Bundle\AdminBundle\Admin\Routing\ToolbarAction;

class FormRouteBuilderTest extends TestCase
{
    public function testBuildFormRouteWithClone()
    {
        $routeBuilder = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles');

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function testBuildFormRouteWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $route = (new FormRouteBuilder('sulu_category.edit_form.details', '/details'))
            ->getRoute();
    }

    public function provideBuildFormRoute()
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
                null,
                null,
                ['test1' => 'value1'],
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
                null,
                null,
                null,
            ],
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
                ['webspace'],
                true,
                null,
            ],
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
                ['webspace', 'id' => 'active'],
                false,
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideBuildFormRoute
     */
    public function testBuildFormRoute(
        string $name,
        string $path,
        string $resourceKey,
        string $formKey,
        ?string $tabTitle,
        ?string $tabCondition,
        ?int $tabOrder,
        ?int $tabPriority,
        ?string $editRoute,
        ?string $backRoute,
        ?array $routerAttributesToBackRoute,
        ?bool $titleVisible,
        ?array $apiOptions
    ) {
        $routeBuilder = (new FormRouteBuilder($name, $path))
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

        if ($routerAttributesToBackRoute) {
            $routeBuilder->addRouterAttributesToBackRoute($routerAttributesToBackRoute);
        }

        if (null !== $titleVisible) {
            $routeBuilder->setTitleVisible($titleVisible);
        }

        if ($apiOptions) {
            $routeBuilder->setApiOptions($apiOptions);
        }

        $route = $routeBuilder->getRoute();

        $this->assertSame($name, $route->getName());
        $this->assertSame($path, $route->getPath());
        $this->assertSame($resourceKey, $route->getOption('resourceKey'));
        $this->assertSame($formKey, $route->getOption('formKey'));
        $this->assertSame($tabTitle, $route->getOption('tabTitle'));
        $this->assertSame($tabCondition, $route->getOption('tabCondition'));
        $this->assertSame($tabOrder, $route->getOption('tabOrder'));
        $this->assertSame($tabPriority, $route->getOption('tabPriority'));
        $this->assertSame($editRoute, $route->getOption('editRoute'));
        $this->assertSame($backRoute, $route->getOption('backRoute'));
        $this->assertSame($routerAttributesToBackRoute, $route->getOption('routerAttributesToBackRoute'));
        $this->assertSame($titleVisible, $route->getOption('titleVisible'));
        $this->assertSame($apiOptions, $route->getOption('apiOptions'));
        $this->assertNull($route->getParent());
        $this->assertSame('sulu_admin.form', $route->getView());
    }

    public function testBuildFormWithToolbarActions()
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addToolbarActions([$saveToolbarAction, $typesToolbarAction])
            ->addToolbarActions([$deleteToolbarAction])
            ->getRoute();

        $this->assertSame(
            [$saveToolbarAction, $typesToolbarAction, $deleteToolbarAction],
            $route->getOption('toolbarActions')
        );
    }

    public function testBuildFormWithRouterAttributesToFormStore()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToFormStore(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToFormStore(['locale'])
            ->getRoute();

        $this->assertSame(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToFormStore')
        );
    }

    public function testBuildFormWithRouterAttributesToEditRoute()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToEditRoute(['webspace', 'parent'])
            ->addRouterAttributesToEditRoute(['locale'])
            ->getRoute();

        $this->assertSame(
            ['webspace', 'parent', 'locale'],
            $route->getOption('routerAttributesToEditRoute')
        );
    }

    public function testBuildFormWithIdQueryParameter()
    {
        $route = (new FormRouteBuilder('sulu_security.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setIdQueryParameter('contactId')
            ->getRoute();

        $this->assertSame(
            'contactId',
            $route->getOption('idQueryParameter')
        );
    }

    public function testBuildFormWithParent()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setParent('sulu_admin.test')
            ->getRoute();

        $this->assertSame('sulu_admin.test', $route->getParent());
    }

    public function testBuildFormWithOption()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setOption('resourceKey', 'test')
            ->getRoute();

        $this->assertSame('test', $route->getOption('resourceKey'));
    }

    public function testBuildFormWithLocales()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getRoute();

        $this->assertSame(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
    }

    public function testBuildFormWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new FormRouteBuilder('sulu_role.list', '/roles'))
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

        $route = (new FormRouteBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->getRoute();
    }

    public function testBuildFormWithRedirectToItself()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('"editRoute"');

        $route = (new FormRouteBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setEditRoute('sulu_role.list')
            ->getRoute();
    }
}
