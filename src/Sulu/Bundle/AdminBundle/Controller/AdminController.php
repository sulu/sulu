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

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\JsConfigPool;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\DatagridInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\FormInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataPool;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\SchemaInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\TypesInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

class AdminController
{
    const TRANSLATION_DOMAIN = 'admin';

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
    private $translator;

    /**
     * @var ResourceMetadataPool
     */
    private $resourceMetadataPool;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $adminName;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var string
     */
    private $suluVersion;

    /**
     * @var array
     */
    private $translatedLocales;

    /**
     * @var array
     */
    private $translations;

    /**
     * @var string
     */
    private $fallbackLocale;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        UrlGeneratorInterface $urlGenerator,
        TokenStorageInterface $tokenStorage,
        AdminPool $adminPool,
        JsConfigPool $jsConfigPool,
        SerializerInterface $serializer,
        ViewHandlerInterface $viewHandler,
        EngineInterface $engine,
        LocalizationManagerInterface $localizationManager,
        TranslatorBagInterface $translator,
        ResourceMetadataPool $resourceMetadataPool,
        $environment,
        $adminName,
        array $locales,
        $suluVersion,
        $translatedLocales,
        $translations,
        $fallbackLocale
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->adminPool = $adminPool;
        $this->jsConfigPool = $jsConfigPool;
        $this->serializer = $serializer;
        $this->viewHandler = $viewHandler;
        $this->engine = $engine;
        $this->localizationManager = $localizationManager;
        $this->translator = $translator;
        $this->resourceMetadataPool = $resourceMetadataPool;
        $this->environment = $environment;
        $this->adminName = $adminName;
        $this->locales = $locales;
        $this->suluVersion = $suluVersion;
        $this->translatedLocales = $translatedLocales;
        $this->translations = $translations;
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * Renders admin ui.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @deprecated Should be replaced with indexV2Action
     */
    public function indexAction()
    {
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return new RedirectResponse($this->urlGenerator->generate('sulu_admin.login', []));
        }

        $user = $this->tokenStorage->getToken()->getUser();

        // get js config from bundles
        $jsConfig = $this->jsConfigPool->getConfigParams();

        // render template
        if ('dev' === $this->environment) {
            $template = 'SuluAdminBundle:Admin:index.html.twig';
        } else {
            $template = 'SuluAdminBundle:Admin:index.html.dist.twig';
        }

        return $this->engine->renderResponse(
            $template,
            [
                'name' => $this->adminName,
                'locales' => array_keys($this->localizationManager->getLocalizations()),
                'translated_locales' => $this->translatedLocales,
                'translations' => $this->translations,
                'fallback_locale' => $this->fallbackLocale,
                'suluVersion' => $this->suluVersion,
                'user' => $this->serializer->serialize(
                    $user,
                    'array',
                    SerializationContext::create()->setGroups(['frontend'])
                ),
                'config' => $jsConfig,
            ]
        );
    }

    public function indexV2Action()
    {
        return $this->engine->renderResponse('SuluAdminBundle:Admin:main.html.twig');
    }

    /**
     * Returns all the configuration for the admin interface.
     */
    public function configV2Action(): Response
    {
        $view = View::create([
            'sulu_admin' => [
                'routes' => $this->adminPool->getRoutes(),
            ],
        ]);
        $view->setFormat('json');

        return $this->viewHandler->handle($view);
    }

    public function translationsAction(Request $request): Response
    {
        $catalogue = $this->translator->getCatalogue($request->query->get('locale'));
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
     * Returns a array of all bundles.
     *
     * @return JsonResponse
     *
     * @deprecated Will not be needed anymore with the new version of the admin and be removed in 2.0
     */
    public function bundlesAction()
    {
        $admins = [];

        foreach ($this->adminPool->getAdmins() as $admin) {
            $name = $admin->getJsBundleName();
            if (null !== $name) {
                $admins[] = $name;
            }
        }

        return new JsonResponse($admins);
    }

    /**
     * Returns contexts of admin.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contextsAction(Request $request)
    {
        $contexts = $this->adminPool->getSecurityContexts();
        $mappedContexts = [];
        foreach ($contexts as $system => $sections) {
            foreach ($sections as $section => $contexts) {
                foreach ($contexts as $context => $permissionTypes) {
                    $this->addContext($mappedContexts, $system, $section, $context, $permissionTypes);
                }
            }
        }

        $requestedSystem = $request->get('system');
        $response = (null !== $requestedSystem) ? $mappedContexts[$requestedSystem] : $mappedContexts;

        return new JsonResponse($response);
    }

    /**
     * Returns config for admin.
     *
     * @return JsonResponse
     */
    public function configAction()
    {
        return new JsonResponse($this->jsConfigPool->getConfigParams());
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
