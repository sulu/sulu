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

use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class SnippetAdminTest extends \PHPUnit_Framework_TestCase
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
        $formRoute = $routes[1];
        $detailRoute = $routes[2];
        $taxonomiesRoute = $routes[3];

        $this->assertAttributeEquals('sulu_snippet.list', 'name', $listRoute);
        $this->assertAttributeSame([
            'title' => 'sulu_snippet.snippets',
            'resourceKey' => 'snippets',
            'editRoute' => 'sulu_snippet.form',
            'locales' => array_keys($locales),
        ], 'options', $listRoute);
        $this->assertAttributeEquals('sulu_snippet.form', 'name', $formRoute);
        $this->assertAttributeEquals('sulu_snippet.form.detail', 'name', $detailRoute);
        $this->assertAttributeEquals('sulu_snippet.form', 'parent', $detailRoute);
        $this->assertAttributeSame([
            'resourceKey' => 'snippets',
            'backRoute' => 'sulu_snippet.list',
            'locales' => array_keys($locales),
        ], 'options', $detailRoute);
        $this->assertAttributeEquals('sulu_snippet.form.taxonomies', 'name', $taxonomiesRoute);
        $this->assertAttributeEquals('sulu_snippet.form', 'parent', $taxonomiesRoute);
        $this->assertAttributeSame([
            'resourceKey' => 'snippets',
            'backRoute' => 'sulu_snippet.list',
            'locales' => array_keys($locales),
        ], 'options', $taxonomiesRoute);
    }
}
