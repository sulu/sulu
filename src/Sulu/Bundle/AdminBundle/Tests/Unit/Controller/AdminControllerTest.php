<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\RouteRegistry;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

class AdminControllerTest extends TestCase
{
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
     * @var TranslatorBagInterface
     */
    private $translatorBag;

    /**
     * @var MetadataProviderRegistry
     */
    private $metadataProviderRegistry;

    /**
     * @var RouteRegistry
     */
    private $routeRegistry;

    /**
     * @var NavigationRegistry
     */
    private $navigationRegistry;

    /**
     * @var FieldTypeOptionRegistryInterface
     */
    private $fieldTypeOptionRegistry;

    /**
     * @var ContactManagerInterface
     */
    private $contactManager;

    /**
     * @var DataProviderPoolInterface
     */
    private $dataProviderPool;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var LinkProviderPoolInterface
     */
    private $linkProviderPool;

    /**
     * @var string
     */
    private $environment = 'prod';

    /**
     * @var string
     */
    private $suluVersion = '2.0.0-RC1';

    /**
     * @var string
     */
    private $appVersion = '666';

    private $resources = [
        'tags' => [
            'endpoint' => [
                'list' => 'get_tags',
                'detail' => 'get_tag',
            ],
        ],
    ];

    /**
     * @var array
     */
    private $locales = ['de', 'en'];

    /**
     * @var array
     */
    private $translations = ['de', 'en'];

    /**
     * @var string
     */
    private $fallbackLocale = 'de';

    /**
     * @var string
     */
    private $previewDelay = 500;

    /**
     * @var string
     */
    private $previewMode = 'off';

    /**
     * @var AdminController
     */
    private $adminController;

    public function setUp()
    {
        parent::setUp();

        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(User::class);
        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->engine = $this->prophesize(EngineInterface::class);
        $this->translatorBag = $this->prophesize(TranslatorBagInterface::class);
        $this->metadataProviderRegistry = $this->prophesize(MetadataProviderRegistry::class);
        $this->routeRegistry = $this->prophesize(RouteRegistry::class);
        $this->navigationRegistry = $this->prophesize(NavigationRegistry::class);
        $this->fieldTypeOptionRegistry = $this->prophesize(FieldTypeOptionRegistryInterface::class);
        $this->contactManager = $this->prophesize(ContactManagerInterface::class);
        $this->dataProviderPool = $this->prophesize(DataProviderPoolInterface::class);
        $this->teaserProviderPool = $this->prophesize(TeaserProviderPoolInterface::class);
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->linkProviderPool = $this->prophesize(LinkProviderPoolInterface::class);

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->adminController = new AdminController(
            $this->urlGenerator->reveal(),
            $this->tokenStorage->reveal(),
            $this->adminPool->reveal(),
            $this->serializer->reveal(),
            $this->viewHandler->reveal(),
            $this->engine->reveal(),
            $this->translatorBag->reveal(),
            $this->metadataProviderRegistry->reveal(),
            $this->routeRegistry->reveal(),
            $this->navigationRegistry->reveal(),
            $this->fieldTypeOptionRegistry->reveal(),
            $this->contactManager->reveal(),
            $this->dataProviderPool->reveal(),
            $this->teaserProviderPool->reveal(),
            $this->managerRegistry->reveal(),
            $this->linkProviderPool->reveal(),
            $this->environment,
            $this->suluVersion,
            $this->appVersion,
            $this->resources,
            $this->locales,
            $this->translations,
            $this->fallbackLocale,
            $this->previewDelay,
            $this->previewMode
        );
    }

    public function testConfigAction()
    {
        $routes = [
            new Route('sulu_snippet.list', '/snippets', 'sulu_admin.list'),
        ];
        $this->routeRegistry->getRoutes()->willReturn($routes);

        $navigation = $this->prophesize(Navigation::class);
        $navigation->getChildrenAsArray()->willReturn(['navigation_item1', 'navigation_item2']);
        $this->navigationRegistry->getNavigation()->willReturn($navigation->reveal());

        $this->urlGenerator->generate('route_id_1')->willReturn('/path1');
        $this->urlGenerator->generate('route_id_2')->willReturn('/path2');
        $this->urlGenerator->generate('sulu_admin.metadata', ['type' => ':type', 'key' => ':key'])
            ->willReturn('/admin/metadata');
        $this->urlGenerator->generate('sulu_preview.start')->willReturn('/preview/start');
        $this->urlGenerator->generate('sulu_preview.render')->willReturn('/preview/render');
        $this->urlGenerator->generate('sulu_preview.update')->willReturn('/preview/update');
        $this->urlGenerator->generate('sulu_preview.update-context')->willReturn('/preview/update-context');
        $this->urlGenerator->generate('sulu_preview.stop')->willReturn('/preview/stop');
        $this->urlGenerator->generate('cget_contexts')->willReturn('/security/contexts');
        $this->urlGenerator->generate('sulu_website.cache.remove')->willReturn('/admin/website/cache');
        $this->urlGenerator->generate('sulu_media.redirect', ['id' => ':id'])->willReturn('/media/redirect');

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $fieldTypeOptions = ['selection' => []];
        $this->fieldTypeOptionRegistry->toArray()->willReturn($fieldTypeOptions);

        $dataProviders = [];
        $this->dataProviderPool->getAll()->willReturn($dataProviders);

        $teaserProviders = [];
        $this->teaserProviderPool->getConfiguration()->willReturn($teaserProviders);

        $addressTypeRepository = $this->prophesize(EntityRepository::class);
        $this->managerRegistry
             ->getRepository('SuluContactBundle:AddressType')
             ->willReturn($addressTypeRepository->reveal());

        $countryRepository = $this->prophesize(EntityRepository::class);
        $this->managerRegistry
             ->getRepository('SuluContactBundle:Country')
             ->willReturn($countryRepository->reveal());

        $this->viewHandler->handle(
            Argument::that(
                function(View $view) use ($dataProviders, $teaserProviders, $fieldTypeOptions, $routes) {
                    $data = $view->getData()['sulu_admin'];

                    return 'json' === $view->getFormat()
                        && $data['fieldTypeOptions'] === $fieldTypeOptions
                        && $data['smartContent'] === $dataProviders
                        && $data['teaser'] === $teaserProviders
                        && $data['routes'] === $routes
                        && $data['navigation'] === ['navigation_item1', 'navigation_item2']
                        && $data['resources'] === $this->resources;
                }
            )
        )->shouldBeCalled()->willReturn(new Response());

        $this->adminController->configAction();
    }

    public function testMetadataAction()
    {
        $form = new FormMetadata();

        $this->user->getLocale()->willReturn('en');

        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata('pages', 'en')->willReturn($form);
        $this->metadataProviderRegistry->getMetadataProvider('form')->willReturn($metadataProvider);

        $this->viewHandler->handle(Argument::that(function(View $view) use ($form) {
            return $form === $view->getData();
        }))->shouldBeCalled()->willReturn(new Response());

        $this->adminController->metadataAction('form', 'pages');
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

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $catalogue = $this->prophesize(MessageCatalogueInterface::class);
        $catalogue->all('admin')->willReturn(['save' => 'Save']);
        $catalogue->getFallbackCatalogue()->willReturn(null);
        $this->translatorBag->getCatalogue('en')->willReturn($catalogue->reveal());

        $response = $this->adminController->translationsAction($request);
        $this->assertEquals(['save' => 'Save'], json_decode($response->getContent(), true));
    }
}
