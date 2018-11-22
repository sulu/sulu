<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManager;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class MediaAdmin extends Admin
{
    const MEDIA_OVERVIEW_ROUTE = 'sulu_media.overview';

    const EDIT_FORM_ROUTE = 'sulu_media.form';

    const EDIT_FORM_DETAIL_ROUTE = 'sulu_media.form.detail';

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

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManager $localizationManager
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        if ($this->securityChecker->hasPermission('sulu.media.collections', PermissionTypes::VIEW)) {
            $media = new NavigationItem('sulu_media.media');
            $media->setPosition(30);
            $media->setIcon('su-image');
            $media->setMainRoute(static::MEDIA_OVERVIEW_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_DETAIL_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_FORMATS_ROUTE);
            $media->addChildRoute(static::EDIT_FORM_HISTORY_ROUTE);

            $rootNavigationItem->addChild($media);
        }

        return new Navigation($rootNavigationItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        $mediaLocales = array_values(
            array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->localizationManager->getLocalizations()
            )
        );

        $toolbarActions = ['sulu_admin.save'];

        return [
            (new Route(static::MEDIA_OVERVIEW_ROUTE, '/collections/:locale/:id?', 'sulu_media.overview'))
                ->addOption('locales', $mediaLocales)
                ->addAttributeDefault('locale', $mediaLocales[0]),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/media/:locale/:id')
                ->setResourceKey('media')
                ->addLocales($mediaLocales)
                ->getRoute(),
            (new Route(static::EDIT_FORM_DETAIL_ROUTE, '/details', 'sulu_media.detail'))
                ->addOption('tabTitle', 'sulu_media.information_taxonomy')
                ->addOption('locales', $mediaLocales)
                ->addOption('toolbarActions', $toolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE),
            (new Route(static::EDIT_FORM_FORMATS_ROUTE, '/formats', 'sulu_media.formats'))
                ->addOption('tabTitle', 'sulu_media.formats')
                ->setParent(static::EDIT_FORM_ROUTE),
            (new Route(static::EDIT_FORM_HISTORY_ROUTE, '/history', 'sulu_media.history'))
                ->addOption('tabTitle', 'sulu_media.history')
                ->setParent(static::EDIT_FORM_ROUTE),
        ];
    }

    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Media' => [
                    'sulu.media.collections' => [
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
}
