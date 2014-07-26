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

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Content\Structure;

class SuluContentContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Content');

    }

    private function getSeo()
    {
        $seo = new NavigationItem('content-navigation.contents.seo');
        $seo->setAction('seo');
        $seo->setContentType('content');
        $seo->setContentComponent('content/seo@sulucontent');
        $seo->setContentDisplay(array('edit'));

        return $seo;
    }

    private function getExcerpt()
    {
        $excerpt = new NavigationItem('content-navigation.contents.excerpt');
        $excerpt->setAction('excerpt');
        $excerpt->setContentType('content');
        $excerpt->setContentComponent('content/excerpt@sulucontent');
        $excerpt->setContentDisplay(array('edit'));

        return $excerpt;
    }

    private function getSettings()
    {
        $settings = new NavigationItem('content-navigation.contents.settings');
        $settings->setAction('settings');
        $settings->setContentType('content');
        $settings->setContentComponent('content/settings@sulucontent');
        $settings->setContentDisplay(array('edit'));

        return $settings;
    }

    private function getContent()
    {
        $content = new NavigationItem('content-navigation.contents.content');
        $content->setAction('content');
        $content->setContentType('content');
        $content->setContentComponent('content/form@sulucontent');

        return $content;
    }

    private function getExternalLink()
    {
        $tab = new NavigationItem('content-navigation.contents.external-link');
        $tab->setAction('content');
        $tab->setContentType('content');
        $tab->setContentComponent('content/external@sulucontent');

        return $tab;
    }

    private function getInternalLink()
    {
        $tab = new NavigationItem('content-navigation.contents.internal-link');
        $tab->setAction('content');
        $tab->setContentType('content');
        $tab->setContentComponent('content/internal@sulucontent');

        return $tab;
    }

    /**
     * generate content navigation
     * @param $showSettings
     * @param $type
     */
    public function generate($showSettings, $type)
    {
        if ($type === Structure::NODE_TYPE_INTERNAL_LINK) {
            $this->addNavigationItem($this->getInternalLink());
        } else if ($type === Structure::NODE_TYPE_EXTERNAL_LINK) {
            $this->addNavigationItem($this->getExternalLink());
        } else {
            $this->addNavigationItem($this->getContent());
        }

        $this->addNavigationItem($this->getSeo());
        $this->addNavigationItem($this->getExcerpt());

        if ($showSettings) {
            $this->addNavigationItem($this->getSettings());
        }
    }
}
