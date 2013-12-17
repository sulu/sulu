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

class SuluContentContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Content');

        $details = new NavigationItem('Details');
        $details->setContentType('content');
        $details->setAction('details');

        $this->addNavigationItem($details);
    }
}
