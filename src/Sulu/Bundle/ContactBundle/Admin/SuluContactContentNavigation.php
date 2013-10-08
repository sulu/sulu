<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluContactContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Contacts');
        $this->setHeader(array(
            'title'         => 'back to contacts',
            'displayOption' => 'link',
            'action'        => 'contacts/contacts'
        ));

        $details = new NavigationItem('Details');
        $details->setContentType('contact');
        $details->setAction('details');
        $details->setType('content');

        $this->addNavigationItem($details);
    }
}
