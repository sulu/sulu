<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteCollection;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Localization\Manager\LocalizationManager;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MediaAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.media.collections';

    const MEDIA_OVERVIEW_ROUTE = 'sulu_media.overview';

    const EDIT_FORM_ROUTE = 'sulu_media.form';

    const EDIT_FORM_DETAILS_ROUTE = 'sulu_media.form.details';

    const EDIT_FORM_FORMATS_ROUTE = 'sulu_media.form.formats';

    const EDIT_FORM_HISTORY_ROUTE = 'sulu_media.form.history';

    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManager $localizationManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $media = new NavigationItem('sulu_media.media');
            $media->setPosition(30);
            $media->setIcon('su-image');
            $media->setMainRoute(static::MEDIA_OVERVIEW_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_DETAILS_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_FORMATS_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_HISTORY_ROUTE);

            $rootNavigationItem->addChild($media);
        }

        return new Navigation($rootNavigationItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(RouteCollection $routeCollection): array
    {
        $mediaLocales = $this->localizationManager->getLocales();

        $toolbarActions = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $toolbarActions[] = 'sulu_admin.save';
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $toolbarActions[] = 'sulu_admin.delete';
        }

        $routes = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $routes[] = (new Route(static::MEDIA_OVERVIEW_ROUTE, '/collections/:locale/:id?', 'sulu_media.overview'))
                ->setOption('locales', $mediaLocales)
                ->setOption('permissions', [
                    'add' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD),
                    'delete' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE),
                    'edit' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT),
                ])
                ->setAttributeDefault('locale', $mediaLocales[0]);
            $routes[] = $this->routeBuilderFactory
                ->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/media/:locale/:id')
                ->setResourceKey('media')
                ->addLocales($mediaLocales)
                ->setTitleProperty('title')
                ->getRoute();
            $routes[] = $this->routeBuilderFactory->createFormRouteBuilder(static::EDIT_FORM_DETAILS_ROUTE, '/details')
                ->setResourceKey('media')
                ->setFormKey('media_details')
                ->setTabTitle('sulu_media.information_taxonomy')
                ->setEditRoute(static::EDIT_FORM_DETAILS_ROUTE)
                ->addToolbarActions($toolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->setBackRoute(static::MEDIA_OVERVIEW_ROUTE)
                ->getRoute();
            $routes[] = (new Route(static::EDIT_FORM_FORMATS_ROUTE, '/formats', 'sulu_media.formats'))
                ->setOption('tabTitle', 'sulu_media.formats')
                ->setParent(static::EDIT_FORM_ROUTE);
            $routes[] = (new Route(static::EDIT_FORM_HISTORY_ROUTE, '/history', 'sulu_media.history'))
                ->setOption('tabTitle', 'sulu_media.history')
                ->setParent(static::EDIT_FORM_ROUTE);
        }

        return $routes;
    }

    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Media' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::SECURITY,
                    ],
                    'sulu.media.system_collections' => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ],
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_media';
    }

    public function getConfig(): ?array
    {
        return [
            'endpoints' => [
                'image_format' => $this->urlGenerator->generate(
                    'sulu_media.redirect',
                    ['id' => ':id']
                ),
            ],
            'media_permissions' => [
                'add' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD),
                'delete' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE),
                'edit' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT),
                'security' => $this->securityChecker->hasPermission(
                    static::SECURITY_CONTEXT,
                    PermissionTypes::SECURITY
                ),
            ],
        ];
    }
}
