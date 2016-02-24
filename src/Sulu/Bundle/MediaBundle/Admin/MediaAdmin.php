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
use Sulu\Bundle\AdminBundle\Navigation\DataNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class MediaAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker, $title)
    {
        $this->securityChecker = $securityChecker;

        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('');
        $section->setPosition(10);

        $media = new NavigationItem('navigation.media');
        $media->setPosition(30);
        $media->setIcon('image');

        if ($this->securityChecker->hasPermission('sulu.media.collections', PermissionTypes::VIEW)) {
            $collections = new DataNavigationItem('navigation.media.collections', '/admin/api/collections?sortBy=title', $media);
            $collections->setPosition(10);
            $collections->setId('collections-edit');
            $collections->setAction('media/collections/root');
            $collections->setInstanceName('collections');
            $collections->setDataNameKey('title');
            $collections->setDataResultKey('collections');
            $collections->setShowAddButton(true);
            $collections->setTitleTranslationKey('navigation.media.collections');
            $collections->setNoDataTranslationKey('navigation.media.collections.empty');
            $collections->setAddButtonTranslationKey('navigation.media.collections.add');
            $collections->setSearchTranslationKey('navigation.media.collections.search');
        }

        if ($media->hasChildren()) {
            $section->addChild($media);
            $rootNavigationItem->addChild($section);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulumedia';
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
                ],
            ],
        ];
    }
}
