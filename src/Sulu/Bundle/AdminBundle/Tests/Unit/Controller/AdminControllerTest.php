<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Controller;

use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\JsConfigPool;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var JsConfigPool
     */
    private $jsConfigPool;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var string
     */
    private $environment = 'prod';

    /**
     * @var string
     */
    private $adminName = 'SULU 2';

    /**
     * @var array
     */
    private $locales = ['de', 'en'];

    /**
     * @var string
     */
    private $suluVersion = '1.1.1';

    /**
     * @var array
     */
    private $translatedLocales = ['de', 'en'];

    /**
     * @var array
     */
    private $translations = ['de', 'en'];

    /**
     * @var string
     */
    private $fallbackLocale = 'de';

    /**
     * @var AdminController
     */
    private $adminController;

    public function setUp()
    {
        parent::setUp();

        $this->authorizationChecker = $this->prophesize(AuthorizationCheckerInterface::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->jsConfigPool = $this->prophesize(JsConfigPool::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->engine = $this->prophesize(EngineInterface::class);
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);

        $this->adminController = new AdminController(
            $this->authorizationChecker->reveal(),
            $this->urlGenerator->reveal(),
            $this->tokenStorage->reveal(),
            $this->adminPool->reveal(),
            $this->jsConfigPool->reveal(),
            $this->serializer->reveal(),
            $this->engine->reveal(),
            $this->localizationManager->reveal(),
            $this->environment,
            $this->adminName,
            $this->locales,
            $this->suluVersion,
            $this->translatedLocales,
            $this->translations,
            $this->fallbackLocale
        );
    }

    public function testContextsAction()
    {
        $request = $this->prophesize(Request::class);

        $this->adminPool->getSecurityContexts()->willReturn(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.sulu_io' => [
                            PermissionTypes::VIEW,
                            PermissionTypes::ADD,
                            PermissionTypes::EDIT,
                            PermissionTypes::DELETE,
                            PermissionTypes::LIVE,
                            PermissionTypes::SECURITY,
                        ],
                    ],
                ],
            ]
        );

        $response = $this->adminController->contextsAction($request->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            '{"Sulu":{"Webspaces":{"sulu.webspaces.sulu_io":["view","add","edit","delete","live","security"]}}}',
            $response->getContent()
        );
    }

    public function testContextsActionFallback()
    {
        $request = $this->prophesize(Request::class);

        $this->adminPool->getSecurityContexts()->willReturn(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.sulu_io',
                    ],
                ],
            ]
        );

        $response = $this->adminController->contextsAction($request->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            '{"Sulu":{"Webspaces":{"sulu.webspaces.sulu_io":["view","add","edit","delete","security"]}}}',
            $response->getContent()
        );
    }

    public function testContextsActionMultipleSystems()
    {
        $request = $this->prophesize(Request::class);

        $this->adminPool->getSecurityContexts()->willReturn(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.sulu_io' => [
                            PermissionTypes::VIEW,
                        ],
                    ],
                ],
                'Website' => [
                    'Test' => [
                        'sulu.test' => [
                            PermissionTypes::VIEW,
                        ],
                    ],
                ],
            ]
        );

        $response = $this->adminController->contextsAction($request->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            '{"Sulu":{"Webspaces":{"sulu.webspaces.sulu_io":["view"]}},"Website":{"Test":{"sulu.test":["view"]}}}',
            $response->getContent()
        );
    }

    public function testContextsActionWithSystem1()
    {
        $request = $this->prophesize(Request::class);
        $request->get('system')->willReturn('Sulu');

        $this->adminPool->getSecurityContexts()->willReturn(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.sulu_io' => [
                            PermissionTypes::VIEW,
                        ],
                    ],
                ],
                'Website' => [
                    'Test' => [
                        'sulu.test' => [
                            PermissionTypes::VIEW,
                        ],
                    ],
                ],
            ]
        );

        $response = $this->adminController->contextsAction($request->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            '{"Webspaces":{"sulu.webspaces.sulu_io":["view"]}}',
            $response->getContent()
        );
    }

    public function testContextsActionWithSystem2()
    {
        $request = $this->prophesize(Request::class);
        $request->get('system')->willReturn('Website');

        $this->adminPool->getSecurityContexts()->willReturn(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.sulu_io' => [
                            PermissionTypes::VIEW,
                        ],
                    ],
                ],
                'Website' => [
                    'Test' => [
                        'sulu.test' => [
                            PermissionTypes::VIEW,
                        ],
                    ],
                ],
            ]
        );

        $response = $this->adminController->contextsAction($request->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            '{"Test":{"sulu.test":["view"]}}',
            $response->getContent()
        );
    }
}
