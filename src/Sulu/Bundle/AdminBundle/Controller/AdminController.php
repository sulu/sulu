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

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\RouteRegistry;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\DatagridInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Endpoint\EndpointInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\FormInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataPool;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\SchemaInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\TypesInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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
     * @var DataProviderPoolInterface
     */
    private $dataProviderPool;

    /**
     * @var string
     */
    private $environment;

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
        ResourceMetadataPool $resourceMetadataPool,
        RouteRegistry $routeRegistry,
        NavigationRegistry $navigationRegistry,
        RouterInterface $router,
        FieldTypeOptionRegistryInterface $fieldTypeOptionRegistry,
        ContactManagerInterface $contactManager,
        DataProviderPoolInterface $dataProviderPool,
        $environment,
        array $locales,
        $translations,
        $fallbackLocale,
        $previewDelay,
        $previewMode
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->adminPool = $adminPool;
        $this->serializer = $serializer;
        $this->viewHandler = $viewHandler;
        $this->engine = $engine;
        $this->translatorBag = $translatorBag;
        $this->resourceMetadataPool = $resourceMetadataPool;
        $this->routeRegistry = $routeRegistry;
        $this->navigationRegistry = $navigationRegistry;
        $this->router = $router;
        $this->fieldTypeOptionRegistry = $fieldTypeOptionRegistry;
        $this->contactManager = $contactManager;
        $this->dataProviderPool = $dataProviderPool;
        $this->environment = $environment;
        $this->locales = $locales;
        $this->translations = $translations;
        $this->fallbackLocale = $fallbackLocale;
        $this->previewDelay = $previewDelay;
        $this->previewMode = $previewMode;
    }

    public function indexAction()
    {
        $endpoints = [
            'config' => $this->router->generate('sulu_admin.config'),
            'items' => $this->router->generate('get_items'),
            'loginCheck' => $this->router->generate('sulu_admin.login_check'),
            'logout' => $this->router->generate('sulu_admin.logout'),
            'reset' => $this->router->generate('sulu_security.reset_password.email'),
            'resetResend' => $this->router->generate('sulu_security.reset_password.email.resend'),
            'resources' => $this->router->generate('sulu_admin.resources', ['resource' => ':resource']),
            'translations' => $this->router->generate('sulu_admin.translation'),
            'generateUrl' => $this->router->generate('post_resourcelocator', ['action' => 'generate']),
        ];

        return $this->engine->renderResponse(
            'SuluAdminBundle:Admin:main.html.twig',
            [
                'translations' => $this->translations,
                'fallback_locale' => $this->fallbackLocale,
                'endpoints' => $endpoints,
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
                $resourceMetadataEndpoints[$resourceMetadata->getKey()] = $this->router->generate($resourceMetadata->getEndpoint());
            }
        }

        $view = View::create([
            'sulu_admin' => [
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
            'sulu_preview' => [
                'routes' => [
                    'start' => $this->urlGenerator->generate('sulu_preview.start'),
                    'render' => $this->urlGenerator->generate('sulu_preview.render'),
                    'update' => $this->urlGenerator->generate('sulu_preview.update'),
                    'stop' => $this->urlGenerator->generate('sulu_preview.stop'),
                ],
                'debounceDelay' => $this->previewDelay,
                'mode' => $this->previewMode,
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

    public function resourcesAction($resource): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var ResourceMetadataInterface $resourceMetadata */
        $resourceMetadata = $this->resourceMetadataPool->getResourceMetadata(
            $resource,
            $user->getLocale()
        );

        $resourceMetadataArray = [];

        if ($resourceMetadata instanceof TypesInterface) {
            foreach ($resourceMetadata->getTypes() as $typeName => $type) {
                $resourceMetadataArray['types'][$typeName] = [
                    'name' => $type->getName(),
                    'title' => $type->getTitle(),
                    'form' => $type->getForm(),
                    'schema' => $type->getSchema(),
                ];
            }
        }
        if ($resourceMetadata instanceof FormInterface) {
            $resourceMetadataArray['form'] = $resourceMetadata->getForm();
        }
        if ($resourceMetadata instanceof SchemaInterface) {
            $resourceMetadataArray['schema'] = $resourceMetadata->getSchema();
        }
        if ($resourceMetadata instanceof DatagridInterface) {
            $resourceMetadataArray['datagrid'] = $resourceMetadata->getDatagrid();
        }

        $response = new Response($this->serializer->serialize($resourceMetadataArray, 'json'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Will transform the different representations of permission types to the same representation and adds it to the
     * passed array.
     *
     * @param array $mappedContexts
     * @param string $system
     * @param string $section
     * @param mixed $context
     * @param mixed $permissionTypes
     */
    private function addContext(array &$mappedContexts, $system, $section, $context, $permissionTypes)
    {
        if (is_array($permissionTypes)) {
            $mappedContexts[$system][$section][$context] = $permissionTypes;
        } else {
            $mappedContexts[$system][$section][$permissionTypes] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::SECURITY,
            ];
        }
    }
}
