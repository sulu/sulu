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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactory;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;

class PageAdminTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ViewBuilderFactory
     */
    private $viewBuilderFactory;

    /**
     * @var ObjectProphecy<SecurityChecker>
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<WebspaceCollection>
     */
    private $webspaceCollection;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<SessionManagerInterface>
     */
    private $sessionManager;

    /**
     * @var ObjectProphecy<TeaserProviderPoolInterface>
     */
    private $teaserProviderPool;

    /**
     * @var ActivityViewBuilderFactoryInterface
     */
    private $activityViewBuilderFactory;

    public function setUp(): void
    {
        $this->viewBuilderFactory = new ViewBuilderFactory();
        $this->securityChecker = $this->prophesize(SecurityChecker::class);
        $this->webspaceCollection = $this->prophesize(WebspaceCollection::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->teaserProviderPool = $this->prophesize(TeaserProviderPoolInterface::class);

        $this->activityViewBuilderFactory = new ActivityViewBuilderFactory(
            $this->viewBuilderFactory,
            $this->securityChecker->reveal()
        );

        $this->webspaceManager->getWebspaceCollection()->willReturn($this->webspaceCollection->reveal());
    }

    public function testGetViews(): void
    {
        $this->securityChecker->hasPermission('sulu.webspaces.test-1', 'edit')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.activities.activities', 'view')->willReturn(true);

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

        $this->webspaceManager->getWebspaceCollection()
            ->willReturn(new WebspaceCollection(['test-1' => $webspace1, 'test-2' => $webspace2]));

        $admin = new PageAdmin(
            $this->viewBuilderFactory,
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            $this->teaserProviderPool->reveal(),
            false,
            $this->activityViewBuilderFactory
        );

        $viewCollection = new ViewCollection();
        $admin->configureViews($viewCollection);

        $webspaceView = $viewCollection->get('sulu_page.webspaces')->getView();
        $this->assertSame('sulu_page.webspaces', $webspaceView->getName());
        $this->assertSame('test-1', $webspaceView->getAttributeDefault('webspace'));

        $pageListView = $viewCollection->get('sulu_page.pages_list')->getView();
        $this->assertSame('sulu_page.pages_list', $pageListView->getName());
        $this->assertSame('de', $pageListView->getAttributeDefault('locale'));

        $this->assertTrue($viewCollection->has('sulu_page.page_edit_form.activity'));
    }

    public function testGetConfigWithVersioning(): void
    {
        $admin = new PageAdmin(
            $this->viewBuilderFactory,
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            $this->teaserProviderPool->reveal(),
            true,
            $this->activityViewBuilderFactory
        );

        $webspace1 = new Webspace();
        $webspace1->setName('beta webspace');

        $webspace2 = new Webspace();
        $webspace2->setName('alpha webspace');

        $this->webspaceManager->getWebspaceCollection()->willReturn(new WebspaceCollection([
            'one' => $webspace1,
            'two' => $webspace2,
        ]));

        $this->teaserProviderPool->getConfiguration()->willReturn([]);

        $config = $admin->getConfig();

        $this->assertEquals(
            [
                'teaser' => [],
                'versioning' => true,
                'webspaces' => [
                    'two' => $webspace2,
                    'one' => $webspace1,
                ],
            ],
            $config
        );

        $this->assertEquals(
            [$webspace2, $webspace1],
            \array_values($config['webspaces'])
        );
    }

    public function testGetConfigWithoutVersioning(): void
    {
        $admin = new PageAdmin(
            $this->viewBuilderFactory,
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            $this->teaserProviderPool->reveal(),
            false,
            $this->activityViewBuilderFactory
        );

        $this->webspaceManager->getWebspaceCollection()->willReturn(new WebspaceCollection([]));
        $this->teaserProviderPool->getConfiguration()->willReturn([]);

        $config = $admin->getConfig();

        $this->assertEquals(
            [
                'teaser' => [],
                'versioning' => false,
                'webspaces' => [],
            ],
            $config
        );
    }

    public function testGetSecurityContexts(): void
    {
        $admin = new PageAdmin(
            $this->viewBuilderFactory,
            $this->webspaceManager->reveal(),
            $this->securityChecker->reveal(),
            $this->sessionManager->reveal(),
            $this->teaserProviderPool->reveal(),
            true,
            $this->activityViewBuilderFactory
        );

        $webspace1 = new Webspace();
        $webspace1->setKey('webspace-key-1');

        $webspace2Security = $this->prophesize(Security::class);
        $webspace2Security->getSystem()->willReturn('webspace-security-system-2');

        $webspace2 = new Webspace();
        $webspace2->setKey('webspace-key-2');
        $webspace2->setSecurity($webspace2Security->reveal());

        $this->webspaceManager->getWebspaceCollection()->willReturn(new WebspaceCollection([
            'webspace-key-1' => $webspace1,
            'webspace-key-2' => $webspace2,
        ]));

        $this->assertEquals(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.webspace-key-1' => ['view', 'add', 'edit', 'delete', 'live', 'security'],
                        'sulu.webspaces.webspace-key-2' => ['view', 'add', 'edit', 'delete', 'live', 'security'],
                    ],
                ],
                'webspace-security-system-2' => [
                    'Webspaces' => [
                        'sulu.webspaces.webspace-key-2' => ['view'],
                    ],
                ],
            ],
            $admin->getSecurityContexts()
        );

        $this->assertEquals(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.#webspace#' => ['view', 'add', 'edit', 'delete', 'live', 'security'],
                    ],
                ],
                'webspace-security-system-2' => [
                    'Webspaces' => [
                        'sulu.webspaces.#webspace#' => ['view'],
                    ],
                ],
            ],
            $admin->getSecurityContextsWithPlaceholder()
        );
    }
}
