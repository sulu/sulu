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
use Sulu\Bundle\AdminBundle\Admin\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\RouteRegistry;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Form;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadata;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataPool;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Property;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\Type;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\TypesInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
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
     * @var string
     */
    private $environment = 'prod';

    /**
     * @var string
     */
    private $suluVersion = '2.0.0-RC1';

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
        $this->resourceMetadataPool = $this->prophesize(ResourceMetadataPool::class);
        $this->routeRegistry = $this->prophesize(RouteRegistry::class);
        $this->navigationRegistry = $this->prophesize(NavigationRegistry::class);
        $this->fieldTypeOptionRegistry = $this->prophesize(FieldTypeOptionRegistryInterface::class);
        $this->contactManager = $this->prophesize(ContactManagerInterface::class);
        $this->dataProviderPool = $this->prophesize(DataProviderPoolInterface::class);

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
            $this->resourceMetadataPool->reveal(),
            $this->routeRegistry->reveal(),
            $this->navigationRegistry->reveal(),
            $this->fieldTypeOptionRegistry->reveal(),
            $this->contactManager->reveal(),
            $this->dataProviderPool->reveal(),
            $this->environment,
            $this->suluVersion,
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

        $this->resourceMetadataPool->getAllResourceMetadata('en')->willReturn(
            [$resourceMetadata1->reveal(), $resourceMetadata2->reveal()]
        );

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $fieldTypeOptions = ['selection' => []];
        $this->fieldTypeOptionRegistry->toArray()->willReturn($fieldTypeOptions);

        $dataProviders = [];
        $this->dataProviderPool->getAll()->willReturn($dataProviders);

        $this->viewHandler->handle(Argument::that(function(View $view) use ($dataProviders, $fieldTypeOptions, $routes) {
            $data = $view->getData()['sulu_admin'];

            return 'json' === $view->getFormat()
                && $data['fieldTypeOptions'] === $fieldTypeOptions
                && $data['smartContent'] === $dataProviders
                && $data['routes'] === $routes
                && $data['navigation'] === ['navigation_item1', 'navigation_item2']
                && $data['resourceMetadataEndpoints'] === [
                    'test1' => '/path1',
                    'test2' => '/path2',
                ];
        }))->shouldBeCalled()->willReturn(new Response());

        $this->adminController->configAction();
    }

    public function testMetadataAction()
    {
        $form = new Form();

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
