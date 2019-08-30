<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\RouteCollection;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactory;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class SnippetAdminTest extends TestCase
{
    /**
     * @var RouteBuilderFactory
     */
    private $routeBuilderFactory;

    /**
     * @var SecurityChecker
     */
    private $securityChecker;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function setUp()
    {
        $this->routeBuilderFactory = new RouteBuilderFactory();
        $this->securityChecker = $this->prophesize(SecurityChecker::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
    }

    public function provideConfigureRoutes()
    {
        return [
            [['en' => 'en', 'de' => 'de', 'fr' => 'fr']],
            [['en' => 'en']],
        ];
    }

    /**
     * @dataProvider provideConfigureRoutes
     */
    public function testConfigureRoutes($locales)
    {
        $snippetAdmin = new SnippetAdmin(
            $this->routeBuilderFactory,
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            false,
            'Test'
        );

        $this->securityChecker->hasPermission('sulu.global.snippets', 'add')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'edit')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'delete')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'view')->willReturn(true);

        $this->webspaceManager->getAllLocales()->willReturn(array_values($locales));

        $routeCollection = new RouteCollection();
        $snippetAdmin->configureRoutes($routeCollection);

        $listRoute = $routeCollection->get('sulu_snippet.list')->getRoute();
        $addFormRoute = $routeCollection->get('sulu_snippet.add_form')->getRoute();
        $addDetailRoute = $routeCollection->get('sulu_snippet.add_form.details')->getRoute();
        $editFormRoute = $routeCollection->get('sulu_snippet.edit_form')->getRoute();
        $editDetailRoute = $routeCollection->get('sulu_snippet.edit_form.details')->getRoute();

        $this->assertAttributeEquals('sulu_snippet.list', 'name', $listRoute);
        $this->assertAttributeEquals([
            'title' => 'sulu_snippet.snippets',
            'toolbarActions' => ['sulu_admin.add' => [], 'sulu_admin.delete' => [], 'sulu_admin.export' => []],
            'resourceKey' => 'snippets',
            'listKey' => 'snippets',
            'adapters' => ['table'],
            'addRoute' => 'sulu_snippet.add_form',
            'editRoute' => 'sulu_snippet.edit_form',
            'locales' => array_keys($locales),
        ], 'options', $listRoute);
        $this->assertAttributeEquals(['locale' => array_keys($locales)[0]], 'attributeDefaults', $listRoute);
        $this->assertAttributeEquals('sulu_snippet.add_form', 'name', $addFormRoute);
        $this->assertAttributeEquals([
            'resourceKey' => 'snippets',
            'backRoute' => 'sulu_snippet.list',
            'locales' => array_keys($locales),
        ], 'options', $addFormRoute);
        $this->assertAttributeEquals('sulu_snippet.add_form', 'parent', $addDetailRoute);
        $this->assertAttributeEquals([
            'resourceKey' => 'snippets',
            'tabTitle' => 'sulu_admin.details',
            'formKey' => 'snippet',
            'editRoute' => 'sulu_snippet.edit_form',
            'toolbarActions' => [
                'sulu_admin.save' => [],
                'sulu_admin.type' => [],
                'sulu_admin.delete' => [],
            ],
        ], 'options', $addDetailRoute);
        $this->assertAttributeEquals('sulu_snippet.edit_form', 'name', $editFormRoute);
        $this->assertAttributeEquals([
            'resourceKey' => 'snippets',
            'backRoute' => 'sulu_snippet.list',
            'locales' => array_keys($locales),
            'titleProperty' => 'title',
        ], 'options', $editFormRoute);
        $this->assertAttributeEquals('sulu_snippet.edit_form.details', 'name', $editDetailRoute);
        $this->assertAttributeEquals('sulu_snippet.edit_form', 'parent', $editDetailRoute);
        $this->assertAttributeEquals([
            'resourceKey' => 'snippets',
            'tabTitle' => 'sulu_admin.details',
            'formKey' => 'snippet',
            'toolbarActions' => [
                'sulu_admin.save' => [],
                'sulu_admin.type' => [],
                'sulu_admin.delete' => [],
            ],
        ], 'options', $editDetailRoute);
    }
}
