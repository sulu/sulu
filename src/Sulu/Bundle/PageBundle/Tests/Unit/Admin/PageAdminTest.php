<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactory;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class PageAdminTest extends TestCase
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

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    public function setUp()
    {
        $this->routeBuilderFactory = new RouteBuilderFactory();
        $this->securityChecker = $this->prophesize(SecurityChecker::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->teaserProviderPool = $this->prophesize(TeaserProviderPoolInterface::class);
    }

    public function testGetRoutes()
    {
        $this->securityChecker->hasPermission('sulu.webspaces.test-1', 'edit')->willReturn(true);

        $localization1 = new Localization('de');

        $webspace1 = new Webspace();
        $webspace1->setKey('test-1');
        $webspace1->setLocalizations([$localization1]);
        $webspace1->setDefaultLocalization($localization1);

        $localization2 = new Localization('en');

        $webspace2 = new Webspace();
        $webspace2->setKey('test-2');
        $webspace2->setLocalizations([$localization2]);
        $webspace2->setDefaultLocalization($localization2);

        $webspaceCollection = new WebspaceCollection();
        $webspaceCollection->setWebspaces([$webspace1, $webspace2]);

        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);

        $admin = new PageAdmin(
            $this->routeBuilderFactory,
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            $this->teaserProviderPool->reveal(),
            false
        );

        $route = $admin->getRoutes()[0];
        $this->assertAttributeEquals('sulu_page.webspaces', 'name', $route);
        $this->assertAttributeEquals([
            'webspace' => 'test-1',
        ], 'attributeDefaults', $route);

        $route = $admin->getRoutes()[1];
        $this->assertAttributeEquals('sulu_page.pages_list', 'name', $route);
        $this->assertAttributeEquals([
            'locale' => 'de',
        ], 'attributeDefaults', $route);
    }

    public function testGetConfigWithVersioning()
    {
        $admin = new PageAdmin(
            $this->routeBuilderFactory,
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            $this->teaserProviderPool->reveal(),
            true
        );

        $config = $admin->getConfig();

        $this->assertEquals(true, $config['versioning']);
    }

    public function testGetConfigWithoutVersioning()
    {
        $admin = new PageAdmin(
            $this->routeBuilderFactory,
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            $this->teaserProviderPool->reveal(),
            false
        );

        $config = $admin->getConfig();

        $this->assertEquals(false, $config['versioning']);
    }
}
