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

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\JsConfigPool;
use Sulu\Bundle\AdminBundle\Admin\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\RouteRegistry;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadata;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataPool;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

class AdminControllerTest extends TestCase
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
     * @var TokenInterface
     */
    private $token;

    /**
     * @var User
     */
    private $user;

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
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var TranslatorBagInterface
     */
    private $translatorBag;

    /**
     * @var ResourceMetadataPool
     */
    private $resourceMetadataPool;

    /**
     * @var RouteRegistry
     */
    private $routeRegistry;

    /**
     * @var NavigationRegistry
     */
    private $navigationRegistry;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var FieldTypeOptionRegistryInterface
     */
    private $fieldTypeOptionRegistry;

    /**
     * @var ContactManagerInterface
     */
    private $contactManager;

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
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(User::class);
        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->jsConfigPool = $this->prophesize(JsConfigPool::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->engine = $this->prophesize(EngineInterface::class);
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);
        $this->translatorBag = $this->prophesize(TranslatorBagInterface::class);
        $this->resourceMetadataPool = $this->prophesize(ResourceMetadataPool::class);
        $this->routeRegistry = $this->prophesize(RouteRegistry::class);
        $this->navigationRegistry = $this->prophesize(NavigationRegistry::class);
        $this->router = $this->prophesize(RouterInterface::class);
        $this->fieldTypeOptionRegistry = $this->prophesize(FieldTypeOptionRegistryInterface::class);
        $this->contactManager = $this->prophesize(ContactManagerInterface::class);

        $this->adminController = new AdminController(
            $this->authorizationChecker->reveal(),
            $this->urlGenerator->reveal(),
            $this->tokenStorage->reveal(),
            $this->adminPool->reveal(),
            $this->jsConfigPool->reveal(),
            $this->serializer->reveal(),
            $this->viewHandler->reveal(),
            $this->engine->reveal(),
            $this->localizationManager->reveal(),
            $this->translatorBag->reveal(),
            $this->resourceMetadataPool->reveal(),
            $this->routeRegistry->reveal(),
            $this->navigationRegistry->reveal(),
            $this->router->reveal(),
            $this->fieldTypeOptionRegistry->reveal(),
            $this->contactManager->reveal(),
            $this->environment,
            $this->adminName,
            $this->locales,
            $this->suluVersion,
            $this->translatedLocales,
            $this->translations,
            $this->fallbackLocale
        );
    }

    public function testConfigurationAction()
    {
        $routes = [
            new Route('sulu_snippet.datagrid', '/snippets', 'sulu_admin.datagrid'),
        ];
        $this->routeRegistry->getRoutes()->willReturn($routes);

        $navigation = $this->prophesize(Navigation::class);
        $navigation->getChildrenAsArray()->willReturn(['navigation_item1', 'navigation_item2']);
        $this->navigationRegistry->getNavigation()->willReturn($navigation->reveal());

        $resourceMetadata1 = $this->prophesize(ResourceMetadata::class);
        $resourceMetadata1->getKey()->willReturn('test1');
        $resourceMetadata1->getEndpoint()->willReturn('route_id_1');

        $resourceMetadata2 = $this->prophesize(ResourceMetadata::class);
        $resourceMetadata2->getKey()->willReturn('test2');
        $resourceMetadata2->getEndpoint()->willReturn('route_id_2');

        $this->router->generate('route_id_1')->willReturn('/path1');
        $this->router->generate('route_id_2')->willReturn('/path2');

        $this->resourceMetadataPool->getAllResourceMetadata('en')->willReturn(
            [$resourceMetadata1->reveal(), $resourceMetadata2->reveal()]
        );

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $fieldTypeOptions = ['selection' => []];
        $this->fieldTypeOptionRegistry->toArray()->willReturn($fieldTypeOptions);

        $this->viewHandler->handle(Argument::that(function(View $view) use ($fieldTypeOptions, $routes) {
            $data = $view->getData()['sulu_admin'];

            return 'json' === $view->getFormat()
                && $data['fieldTypeOptions'] === $fieldTypeOptions
                && $data['routes'] === $routes
                && $data['navigation'] === ['navigation_item1', 'navigation_item2']
                && $data['resourceMetadataEndpoints'] === [
                    'test1' => '/path1',
                    'test2' => '/path2',
                ];
        }))->shouldBeCalled()->willReturn(new Response());

        $this->adminController->configV2Action();
    }

    public function provideTranslationsAction()
    {
        return [
            ['en', ['save' => 'Save'], [], ['save' => 'Save']],
            ['de', ['save' => 'Speichern'], [], ['save' => 'Speichern']],
            [
                'de',
                ['save' => 'Speichern'],
                ['save' => 'Save', 'delete' => 'Delete'],
                ['save' => 'Speichern', 'delete' => 'Delete'],
            ],
        ];
    }

    /**
     * @dataProvider provideTranslationsAction
     */
    public function testTranslationsAction($locale, $translations, $fallbackTranslations, $resultTranslations)
    {
        $request = new Request(['locale' => $locale]);

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $catalogue = $this->prophesize(MessageCatalogueInterface::class);
        $catalogue->all('admin')->willReturn($translations);
        $fallbackCatalogue = $this->prophesize(MessageCatalogueInterface::class);
        $fallbackCatalogue->all('admin')->willReturn($fallbackTranslations);
        $catalogue->getFallbackCatalogue()->willReturn($fallbackCatalogue);
        $this->translatorBag->getCatalogue($locale)->willReturn($catalogue->reveal());

        $response = $this->adminController->translationsAction($request);
        $this->assertEquals($resultTranslations, json_decode($response->getContent(), true));
    }

    public function testTranslationActionWithoutFallback()
    {
        $request = new Request(['locale' => 'en']);

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $catalogue = $this->prophesize(MessageCatalogueInterface::class);
        $catalogue->all('admin')->willReturn(['save' => 'Save']);
        $catalogue->getFallbackCatalogue()->willReturn(null);
        $this->translatorBag->getCatalogue('en')->willReturn($catalogue->reveal());

        $response = $this->adminController->translationsAction($request);
        $this->assertEquals(['save' => 'Save'], json_decode($response->getContent(), true));
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
