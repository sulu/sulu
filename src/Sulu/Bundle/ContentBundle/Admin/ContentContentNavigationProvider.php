<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ContentContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var bool
     */
    private $enabledSecurity;

    /**
     * @param SecurityCheckerInterface $securityChecker
     * @param bool $enabledSecurity
     */
    public function __construct(SecurityCheckerInterface $securityChecker, $enabledSecurity = false)
    {
        $this->securityChecker = $securityChecker;
        $this->enabledSecurity = $enabledSecurity;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = array())
    {
        $content = new ContentNavigationItem('content-navigation.contents.content');
        $content->setId('tab-content');
        $content->setAction('content');
        $content->setComponent('content/form@sulucontent');

        $seo = new ContentNavigationItem('content-navigation.contents.seo');
        $seo->setId('tab-seo');
        $seo->setAction('seo');
        $seo->setComponent('content/seo@sulucontent');
        $seo->setDisplay(array('edit'));

        $excerpt = new ContentNavigationItem('content-navigation.contents.excerpt');
        $excerpt->setId('tab-excerpt');
        $excerpt->setAction('excerpt');
        $excerpt->setComponent('content/excerpt@sulucontent');
        $excerpt->setDisplay(array('edit'));

        $settings = new ContentNavigationItem('content-navigation.contents.settings');
        $settings->setId('tab-settings');
        $settings->setAction('settings');
        $settings->setComponent('content/settings@sulucontent');
        $settings->setDisplay(array('edit'));

        $navigation = array($content, $seo, $excerpt, $settings);

        $securityContext = 'sulu.webspaces.' . $options['webspace'];

        if ($this->enabledSecurity && $this->securityChecker->hasPermission($securityContext, 'security')) {
            $permissions = new ContentNavigationItem('Permissions');
            $permissions->setAction('permissions');
            $permissions->setDisplay(array('edit'));
            $permissions->setComponent('permission-tab@sulusecurity');
            $permissions->setComponentOptions(
                array(
                    'display' => 'form',
                    'type' => Structure::class,
                    'securityContext' => $securityContext,
                )
            );

            $navigation[] = $permissions;
        }

        return $navigation;
    }
}
