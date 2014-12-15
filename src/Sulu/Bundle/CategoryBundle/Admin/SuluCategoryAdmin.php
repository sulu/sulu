<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface;

class SuluCategoryAdmin extends Admin
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

        $settings = new NavigationItem('navigation.settings');
        $settings->setIcon('cog');

        if ($this->securityChecker->hasPermission('sulu.settings.categories', 'view')) {
            $categories = new NavigationItem('navigation.settings.categories', $settings);
            $categories->setAction('settings/categories');
        }

        if ($settings->hasChildren()) {
            $section->addChild($settings);
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
        return 'sulucategory';
    }

    public function getSecurityContexts()
    {
        return array(
            'Sulu' => array(
                'Settings' => array(
                    'sulu.settings.categories'
                )
            )
        );
    }
}
