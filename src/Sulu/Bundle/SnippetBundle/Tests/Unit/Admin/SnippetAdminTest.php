<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class SnippetAdminTest extends TestCase
{
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
        $this->securityChecker = $this->prophesize(SecurityChecker::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
    }

    public function provideGetRoutes()
    {
        return [
            [['en' => 'en', 'de' => 'de', 'fr' => 'fr']],
            [['en' => 'en']],
        ];
    }

    /**
     * @dataProvider provideGetRoutes
     */
    public function testGetRoutes($locales)
    {
        $snippetAdmin = new SnippetAdmin(
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            false,
            'Test'
        );

        $this->webspaceManager->getAllLocalizations()->willReturn(array_map(function($localization) {
            return new Localization($localization);
        }, $locales));

        $routes = $snippetAdmin->getRoutes();
        $listRoute = $routes[0];
        $addFormRoute = $routes[1];
        $addDetailRoute = $routes[2];
        $editFormRoute = $routes[3];
        $editDetailRoute = $routes[4];

        $this->assertAttributeEquals('sulu_snippet.datagrid', 'name', $listRoute);
        $this->assertAttributeSame([
            'title' => 'sulu_snippet.snippets',
            'resourceKey' => 'snippets',
            'adapters' => ['table'],
            'addRoute' => 'sulu_snippet.add_form.detail',
            'editRoute' => 'sulu_snippet.edit_form.detail',
            'locales' => array_keys($locales),
        ], 'options', $listRoute);
        $this->assertAttributeSame(['locale' => array_keys($locales)[0]], 'attributeDefaults', $listRoute);
        $this->assertAttributeEquals('sulu_snippet.add_form', 'name', $addFormRoute);
        $this->assertAttributeEquals([
            'resourceKey' => 'snippets',
            'locales' => array_keys($locales),
            'toolbarActions' => [
                'sulu_admin.save',
                'sulu_admin.type',
                'sulu_admin.delete',
            ],
        ], 'options', $addFormRoute);
        $this->assertAttributeEquals('sulu_snippet.add_form', 'parent', $addDetailRoute);
        $this->assertAttributeSame([
            'tabTitle' => 'sulu_snippet.details',
            'backRoute' => 'sulu_snippet.datagrid',
            'editRoute' => 'sulu_snippet.edit_form.detail',
        ], 'options', $addDetailRoute);
        $this->assertAttributeEquals('sulu_snippet.edit_form', 'name', $editFormRoute);
        $this->assertAttributeEquals([
            'resourceKey' => 'snippets',
            'locales' => array_keys($locales),
            'toolbarActions' => [
                'sulu_admin.save',
                'sulu_admin.type',
                'sulu_admin.delete',
            ],
        ], 'options', $editFormRoute);
        $this->assertAttributeEquals('sulu_snippet.edit_form.detail', 'name', $editDetailRoute);
        $this->assertAttributeEquals('sulu_snippet.edit_form', 'parent', $editDetailRoute);
        $this->assertAttributeSame([
            'tabTitle' => 'sulu_snippet.details',
            'backRoute' => 'sulu_snippet.datagrid',
        ], 'options', $editDetailRoute);
    }
}
