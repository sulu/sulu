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

class SuluContentContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = array())
    {
        $content = new ContentNavigationItem('content-navigation.contents.content');
        $content->setId('tab-content');
        $content->setAction('content');
        $content->setGroups(array('content'));
        $content->setComponent('content/form@sulucontent');

        $seo = new ContentNavigationItem('content-navigation.contents.seo');
        $seo->setId('tab-seo');
        $seo->setAction('seo');
        $seo->setGroups(array('content'));
        $seo->setComponent('content/seo@sulucontent');
        $seo->setDisplay(array('edit'));

        $excerpt = new ContentNavigationItem('content-navigation.contents.excerpt');
        $excerpt->setId('tab-excerpt');
        $excerpt->setAction('excerpt');
        $excerpt->setGroups(array('content'));
        $excerpt->setComponent('content/excerpt@sulucontent');
        $excerpt->setDisplay(array('edit'));

        $settings = new ContentNavigationItem('content-navigation.contents.settings');
        $settings->setId('tab-settings');
        $settings->setAction('settings');
        $settings->setGroups(array('content'));
        $settings->setComponent('content/settings@sulucontent');
        $settings->setDisplay(array('edit'));

        $navigation = array($content, $seo, $excerpt, $settings);

        $permissions = new ContentNavigationItem('Permissions');
        $permissions->setAction('permissions');
        $permissions->setDisplay(array('edit'));
        $permissions->setComponent('permission-tab@sulusecurity');
        $permissions->setComponentOptions(
            array(
                'display' => 'form',
                'type' => Structure::class,
                'securityContext' => 'sulu.webspaces.' . $options['webspace']
            )
        );
        $permissions->setGroups(array('content'));

        $navigation[] = $permissions;

        return $navigation;
    }
}
