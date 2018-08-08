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
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManager;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class MediaAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    public function __construct(
        SecurityCheckerInterface $securityChecker,
        LocalizationManager $localizationManager
    ) {
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
            $media->setMainRoute('sulu_media.overview');
            $media->addChildRoute('sulu_media.form');
            $media->addChildRoute('sulu_media.form.detail');

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

        return [
            (new Route('sulu_media.overview', '/collections/:locale/:id?', 'sulu_media.overview'))
                ->addOption('locales', $mediaLocales)
                ->addAttributeDefault('locale', $mediaLocales[0]),
            (new Route('sulu_media.form', '/media/:locale/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'media')
                ->addOption('toolbarActions', ['sulu_admin.save'])
                ->addOption('locales', $mediaLocales),
            (new Route('sulu_media.form.detail', '/details', 'sulu_media.detail'))
                ->addOption('tabTitle', 'sulu_media.information_taxonomy')
                ->addOption('locales', $mediaLocales)
                ->setParent('sulu_media.form'),
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
