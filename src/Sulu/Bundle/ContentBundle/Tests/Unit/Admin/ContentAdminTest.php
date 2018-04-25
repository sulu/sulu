<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Admin;

use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class ContentAdminTest extends \PHPUnit\Framework\TestCase
{
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

    public function setUp()
    {
        $this->securityChecker = $this->prophesize(SecurityChecker::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
    }

    public function testGetRoutes()
    {
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

        $contentAdmin = new ContentAdmin(
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            'Test'
        );

        $route = $contentAdmin->getRoutes()[0];
        $this->assertAttributeEquals('sulu_content.webspaces', 'name', $route);
        $this->assertAttributeEquals([
            'webspace' => 'test-1',
            'locale' => 'de',
        ], 'attributeDefaults', $route);
    }
}
