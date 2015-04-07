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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluContentContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        $this->setName('Content');

        $this->addNavigationItem($this->getContent());
        $this->addNavigationItem($this->getSeo());
        $this->addNavigationItem($this->getExcerpt());
        $this->addNavigationItem($this->getSettings());
    }

    private function getSeo()
    {
        $seo = new ContentNavigationItem('content-navigation.contents.seo');
        $seo->setId('tab-seo');
        $seo->setAction('seo');
        $seo->setGroups(array('content'));
        $seo->setComponent('content/seo@sulucontent');
        $seo->setDisplay(array('edit'));

        return $seo;
    }

    private function getExcerpt()
    {
        $excerpt = new ContentNavigationItem('content-navigation.contents.excerpt');
        $excerpt->setId('tab-excerpt');
        $excerpt->setAction('excerpt');
        $excerpt->setGroups(array('content'));
        $excerpt->setComponent('content/excerpt@sulucontent');
        $excerpt->setDisplay(array('edit'));

        return $excerpt;
    }

    private function getSettings()
    {
        $settings = new ContentNavigationItem('content-navigation.contents.settings');
        $settings->setId('tab-settings');
        $settings->setAction('settings');
        $settings->setGroups(array('content'));
        $settings->setComponent('content/settings@sulucontent');
        $settings->setDisplay(array('edit'));

        return $settings;
    }

    private function getContent()
    {
        $content = new ContentNavigationItem('content-navigation.contents.content');
        $content->setId('tab-content');
        $content->setAction('content');
        $content->setGroups(array('content'));
        $content->setComponent('content/form@sulucontent');

        return $content;
    }
}
