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

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluTranslateContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Package');

        $details = new NavigationItem('Details');
        $details->setContentType('package');
        $details->setAction('details');
        $details->setType('content');

        $this->addNavigationItem($details);


        $details = new NavigationItem('Settings');
        $details->setContentType('package');
        $details->setAction('settings');
        $details->setType('content');

        $this->addNavigationItem($details);

    }
}
