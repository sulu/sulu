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

class SuluTranslateAdmin extends Admin
{

    public function __construct()
    {
        $rootNavigationItem = new NavigationItem('Root');
        $settings = new NavigationItem('Settings');
        $settings->setIcon('cogwheel');
        $rootNavigationItem->addChild($settings);
        $translate = new NavigationItem('Translate');
        $translate->setAction('settings/translate');
		$translate->setIcon('book');
        $translate->setType('content');
        $settings->addChild($translate);
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
}
