<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Twig\Environment;

class AdminController
{
    public const TRANSLATION_DOMAIN = 'admin';

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
     * @var Environment
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
     * @var ViewRegistry
     */
    private $viewRegistry;

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
     * @var LinkProviderPoolInterface
     */
    private $linkProviderPool;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

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
    private $resources;

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
     * @var string
     */
    private $collaborationInterval;

    /**
     * @var bool
     */
    private $collaborationEnabled;

    /**
     * @var string|null
     */
    private $passwordPattern;

    /**
     * @var string|null
     */
    private $passwordInfoTranslationKey;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TokenStorageInterface $tokenStorage,
        AdminPool $adminPool,
        SerializerInterface $serializer,
        ViewHandlerInterface $viewHandler,
        Environment $engine,
        TranslatorBagInterface $translatorBag,
        MetadataProviderRegistry $metadataProviderRegistry,
        ViewRegistry $viewRegistry,
        NavigationRegistry $navigationRegistry,
        FieldTypeOptionRegistryInterface $fieldTypeOptionRegistry,
        ContactManagerInterface $contactManager,
        DataProviderPoolInterface $dataProviderPool,
        LinkProviderPoolInterface $linkProviderPool,
        LocalizationManagerInterface $localizationManager,
        string $environment,
        string $suluVersion,
        ?string $appVersion,
        array $resources,
        array $locales,
        array $translations,
        string $fallbackLocale,
        string $collaborationInterval,
        ?bool $collaborationEnabled = null,
        ?string $passwordPattern = null,
        ?string $passwordInfoTranslationKey = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->adminPool = $adminPool;
        $this->serializer = $serializer;
        $this->viewHandler = $viewHandler;
        $this->engine = $engine;
        $this->translatorBag = $translatorBag;
        $this->metadataProviderRegistry = $metadataProviderRegistry;
        $this->viewRegistry = $viewRegistry;
        $this->navigationRegistry = $navigationRegistry;
        $this->fieldTypeOptionRegistry = $fieldTypeOptionRegistry;
        $this->contactManager = $contactManager;
        $this->dataProviderPool = $dataProviderPool;
        $this->linkProviderPool = $linkProviderPool;
        $this->localizationManager = $localizationManager;
        $this->environment = $environment;
        $this->suluVersion = $suluVersion;
        $this->appVersion = $appVersion;
        $this->resources = $resources;
        $this->locales = $locales;
        $this->translations = $translations;
        $this->fallbackLocale = $fallbackLocale;
        $this->collaborationInterval = $collaborationInterval;

        if (null === $collaborationEnabled) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Instantiating the AdminController without the $collaborationEnabled argument is deprecated!');
        }

        $this->collaborationEnabled = $collaborationEnabled ?? true;
        $this->passwordPattern = $passwordPattern;
        $this->passwordInfoTranslationKey = $passwordInfoTranslationKey;
    }

    public function indexAction()
    {
        $endpoints = [
            'config' => $this->urlGenerator->generate('sulu_admin.config'),
            'items' => $this->urlGenerator->generate('sulu_page.get_items'),
            'loginCheck' => $this->urlGenerator->generate('sulu_admin.login_check'),
            'logout' => $this->urlGenerator->generate('sulu_admin.logout'),
            'profileSettings' => $this->urlGenerator->generate('sulu_security.patch_profile_settings'),
            'forgotPasswordReset' => $this->urlGenerator->generate('sulu_security.reset_password.email'),
            'resetPassword' => $this->urlGenerator->generate('sulu_security.reset_password.reset'),
            'translations' => $this->urlGenerator->generate('sulu_admin.translation'),
            'generateUrl' => $this->urlGenerator->generate('sulu_page.post_resourcelocator', ['action' => 'generate']),
            'routing' => $this->urlGenerator->generate('fos_js_routing_js'),
        ];

        try {
            $endpoints['twoFactorLoginCheck'] = $this->urlGenerator->generate('2fa_login_check_admin');
        } catch (RouteNotFoundException $e) {
            // @ignoreException ignore if no 2fa_login_check_admin exist
        }

        return new Response($this->engine->render(
            '@SuluAdmin/Admin/main.html.twig',
            [
                'translations' => $this->translations,
                'fallback_locale' => $this->fallbackLocale,
                'endpoints' => $endpoints,
                'password_pattern' => $this->passwordPattern,
                'password_info_translation_key' => $this->passwordInfoTranslationKey,
                'sulu_version' => $this->suluVersion,
                'app_version' => $this->appVersion,
            ]
        ));
    }

    /**
     * Returns all the configuration for the admin interface.
     */
    public function configAction(): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $locale = $user->getLocale();
        $contact = $this->contactManager->getById($user->getContact()->getId(), $locale);

        $config = [
            'sulu_admin' => [
                'fieldTypeOptions' => $this->fieldTypeOptionRegistry->toArray(),
                'internalLinkTypes' => $this->linkProviderPool->getConfiguration(),
                'localizations' => \array_values($this->localizationManager->getLocalizations()),
                'navigation' => \array_map(function(NavigationItem $navigationItem) {
                    return $navigationItem->toArray();
                }, \array_values($this->navigationRegistry->getNavigationItems())),
                'routes' => $this->viewRegistry->getViews(),
                'resources' => $this->resources,
                'smartContent' => \array_map(function(DataProviderInterface $dataProvider) {
                    return $dataProvider->getConfiguration();
                }, $this->dataProviderPool->getAll()),
                'user' => $user,
                'contact' => $contact,
                'collaborationEnabled' => $this->collaborationEnabled,
                'collaborationInterval' => $this->collaborationInterval * 1000,
            ],
        ];

        foreach ($this->adminPool->getAdmins() as $admin) {
            $adminConfigKey = $admin->getConfigKey();
            $adminConfig = $admin->getConfig();

            if ($adminConfigKey && $adminConfig) {
                $config[$adminConfigKey] = $adminConfig;
            }
        }

        $view = View::create($config);

        $context = new Context();
        $context->setGroups(['frontend', 'partialContact', 'fullView']);
        $context->setAttribute('locale', $locale);

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
            $translations = \array_replace($fallbackCatalogue->all(static::TRANSLATION_DOMAIN), $translations);
        }

        if (0 === \count($translations)) {
            $translations = new \stdClass();
        }

        return new JsonResponse($translations);
    }

    public function metadataAction(string $type, string $key, Request $request): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $metadataOptions = $request->query->all();
        $metadata = $this->metadataProviderRegistry->getMetadataProvider($type)
            ->getMetadata($key, $user->getLocale(), $metadataOptions);

        $context = new Context();
        $context->addGroup('Default');
        if (true === \filter_var($metadataOptions['onlyKeys'] ?? 'false', \FILTER_VALIDATE_BOOLEAN)) {
            $context->addGroup('admin_form_metadata_keys_only');
        }

        $view = View::create($metadata);
        $view->setFormat('json');
        $view->setContext($context);

        $response = $this->viewHandler->handle($view);

        if (!$metadata->isCacheable()) {
            $response->headers->addCacheControlDirective('no-store', true);
        }

        return $response;
    }
}
