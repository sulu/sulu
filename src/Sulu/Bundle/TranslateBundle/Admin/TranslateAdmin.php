<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\TranslateBundle\Command\ExportCommand;
use Sulu\Bundle\TranslateBundle\Command\ImportCommand;

class TranslateAdmin extends Admin
{

    public function __construct($title)
    {
        $rootNavigationItem = new NavigationItem($title);

        $section = new NavigationItem('');

        $settings = new NavigationItem('navigation.settings');
        $settings->setIcon('cogwheels');

        $translate = new NavigationItem('navigation.settings.translate');
        $translate->setAction('settings/translate');
        $translate->setIcon('book-open');
        $settings->addChild($translate);

        $section->addChild($settings);
        $rootNavigationItem->addChild($section);
        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return array(
            new ImportCommand(),
            new ExportCommand()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulutranslate';
    }
}
