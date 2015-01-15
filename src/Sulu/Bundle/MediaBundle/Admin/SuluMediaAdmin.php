<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuluMediaAdmin extends Admin
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

        $media = new NavigationItem('navigation.media');
        $media->setIcon('image');

        if ($this->securityChecker->hasPermission('sulu.media.collections', 'view')) {
            $collections = new NavigationItem('navigation.media.collections', $media);
            $collections->setAction('media/collections');
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
    public function getCommands()
    {
        return array();
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
        return array(
            'Sulu' => array(
                'Media' => array(
                    'sulu.media.collections',
                    'sulu.media.files',
                )
            )
        );
    }
}
