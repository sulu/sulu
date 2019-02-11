<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\RouteRegistry;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Endpoint\EndpointInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataPool;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

class AdminController
{
    const TRANSLATION_DOMAIN = 'admin';

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
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $suluVersion;

    /**
     * @var string|null
     */
    private $appVersion;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var array
     */
    private $translations;

    /**
     * @var string
     */
    private $fallbackLocale;

    /**
     * @var int
     */
    private $previewDelay;

    /**
     * @var string
     */
    private $previewMode;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TokenStorageInterface $tokenStorage,
        AdminPool $adminPool,
        SerializerInterface $serializer,
        ViewHandlerInterface $viewHandler,
        EngineInterface $engine,
        TranslatorBagInterface $translatorBag,
        MetadataProviderRegistry $metadataProviderRegistry,
        ResourceMetadataPool $resourceMetadataPool,
        RouteRegistry $routeRegistry,
        NavigationRegistry $navigationRegistry,
        FieldTypeOptionRegistryInterface $fieldTypeOptionRegistry,
        ContactManagerInterface $contactManager,
        DataProviderPoolInterface $dataProviderPool,
        ManagerRegistry $managerRegistry,
        string $environment,
        string $suluVersion,
        ?string $appVersion,
        array $locales,
        array $translations,
        string $fallbackLocale,
        int $previewDelay,
        string $previewMode
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->adminPool = $adminPool;
        $this->serializer = $serializer;
        $this->viewHandler = $viewHandler;
        $this->engine = $engine;
        $this->translatorBag = $translatorBag;
        $this->metadataProviderRegistry = $metadataProviderRegistry;
        $this->resourceMetadataPool = $resourceMetadataPool;
        $this->routeRegistry = $routeRegistry;
        $this->navigationRegistry = $navigationRegistry;
        $this->fieldTypeOptionRegistry = $fieldTypeOptionRegistry;
        $this->contactManager = $contactManager;
        $this->dataProviderPool = $dataProviderPool;
        $this->managerRegistry = $managerRegistry;
        $this->environment = $environment;
        $this->suluVersion = $suluVersion;
        $this->appVersion = $appVersion;
        $this->locales = $locales;
        $this->translations = $translations;
        $this->fallbackLocale = $fallbackLocale;
        $this->previewDelay = $previewDelay;
        $this->previewMode = $previewMode;
    }

    public function indexAction()
    {
        $endpoints = [
            'config' => $this->urlGenerator->generate('sulu_admin.config'),
            'items' => $this->urlGenerator->generate('get_items'),
            'loginCheck' => $this->urlGenerator->generate('sulu_admin.login_check'),
            'logout' => $this->urlGenerator->generate('sulu_admin.logout'),
            'profileSettings' => $this->urlGenerator->generate('patch_profile_settings'),
            'reset' => $this->urlGenerator->generate('sulu_security.reset_password.email'),
            'resetResend' => $this->urlGenerator->generate('sulu_security.reset_password.email.resend'),
            'translations' => $this->urlGenerator->generate('sulu_admin.translation'),
            'generateUrl' => $this->urlGenerator->generate('post_resourcelocator', ['action' => 'generate']),
        ];

        return $this->engine->renderResponse(
            'SuluAdminBundle:Admin:main.html.twig',
            [
                'translations' => $this->translations,
                'fallback_locale' => $this->fallbackLocale,
                'endpoints' => $endpoints,
                'sulu_version' => $this->suluVersion,
                'app_version' => $this->appVersion,
            ]
        );
    }

    /**
     * Returns all the configuration for the admin interface.
     */
    public function configAction(): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $contact = $this->contactManager->getById($user->getContact()->getId(), $user->getLocale());

        $resourceMetadataEndpoints = [];
        foreach ($this->resourceMetadataPool->getAllResourceMetadata($user->getLocale()) as $resourceMetadata) {
            if ($resourceMetadata instanceof EndpointInterface) {
                $resourceMetadataEndpoints[$resourceMetadata->getKey()] = $this->urlGenerator->generate($resourceMetadata->getEndpoint());
            }
        }

        $view = View::create([
            'sulu_admin' => [
                'endpoints' => [
                    'metadata' => $this->urlGenerator->generate(
                        'sulu_admin.metadata',
                        ['type' => ':type', 'key' => ':key']
                    ),
                ],
                'fieldTypeOptions' => $this->fieldTypeOptionRegistry->toArray(),
                'routes' => $this->routeRegistry->getRoutes(),
                'navigation' => $this->navigationRegistry->getNavigation()->getChildrenAsArray(),
                'resourceMetadataEndpoints' => $resourceMetadataEndpoints,
                'smartContent' => array_map(function(DataProviderInterface $dataProvider) {
                    return $dataProvider->getConfiguration();
                }, $this->dataProviderPool->getAll()),
                'user' => $user,
                'contact' => $contact,
            ],
            'sulu_contact' => [
                'addressTypes' => $this->managerRegistry->getRepository('SuluContactBundle:AddressType')->findAll(),
                'countries' => $this->managerRegistry->getRepository('SuluContactBundle:Country')->findAll(),
            ],
            'sulu_page' => [
                'endpoints' => [
                    'clearCache' => $this->urlGenerator->generate('sulu_website.cache.remove'),
                ],
            ],
            'sulu_preview' => [
                'endpoints' => [
                    'start' => $this->urlGenerator->generate('sulu_preview.start'),
                    'render' => $this->urlGenerator->generate('sulu_preview.render'),
                    'update' => $this->urlGenerator->generate('sulu_preview.update'),
                    'update-context' => $this->urlGenerator->generate('sulu_preview.update-context'),
                    'stop' => $this->urlGenerator->generate('sulu_preview.stop'),
                ],
                'debounceDelay' => $this->previewDelay,
                'mode' => $this->previewMode,
            ],
            'sulu_security' => [
                'endpoints' => [
                    'contexts' => $this->urlGenerator->generate('cget_contexts'),
                ],
            ],
        ]);

        $context = new Context();
        $context->setGroups(['frontend', 'partialContact', 'fullRoute']);

        $view->setContext($context);
        $view->setFormat('json');

        return $this->viewHandler->handle($view);
    }

    public function translationsAction(Request $request): Response
    {
        $catalogue = $this->translatorBag->getCatalogue($request->query->get('locale'));
        $fallbackCatalogue = $catalogue->getFallbackCatalogue();

        $translations = $catalogue->all(static::TRANSLATION_DOMAIN);
        if ($fallbackCatalogue) {
            $translations = array_replace($fallbackCatalogue->all(static::TRANSLATION_DOMAIN), $translations);
        }

        return new JsonResponse($translations);
    }

    public function metadataAction(string $type, string $key): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $view = View::create(
            $this->metadataProviderRegistry->getMetadataProvider($type)->getMetadata($key, $user->getLocale())
        );
        $view->setFormat('json');

        return $this->viewHandler->handle($view);
    }
}
