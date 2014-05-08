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

        /* CONTACTS */
        // details
        $details = new NavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setContentType('contact');
        $details->setContentComponent('contacts@sulucontact');
        $details->setContentComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);

        /* ACCOUNTS */
        // details
        $details = new NavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setContentType('account');
        $details->setContentComponent('accounts@sulucontact');
        $details->setContentComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);
        // contacts
        $contacts = new NavigationItem('navigation.contacts');
        $contacts->setAction('contacts');
        $contacts->setContentType('account');
        $contacts->setContentComponent('accounts@sulucontact');
        $contacts->setContentComponentOptions(array('display'=>'contacts'));
        $this->addNavigationItem($contacts);
    }
}
