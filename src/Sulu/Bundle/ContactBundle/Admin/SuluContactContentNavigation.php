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
        // activities
        $activities = new NavigationItem('content-navigation.contacts.activities');
        $activities->setAction('activities');
        $activities->setContentType('contact');
        $activities->setContentComponent('contacts@sulucontact');
        $activities->setContentComponentOptions(array('display'=>'activities'));
        $this->addNavigationItem($activities);

        /* ACCOUNTS */
        // details
        $details = new NavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setId('details');
        $details->setContentType('account');
        $details->setContentComponent('accounts@sulucontact');
        $details->setContentComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);
        // activities
        $activities = new NavigationItem('content-navigation.contacts.activities');
        $activities->setAction('activities');
        $activities->setContentType('account');
        $activities->setContentComponent('accounts@sulucontact');
        $activities->setContentComponentOptions(array('display'=>'activities'));
        $this->addNavigationItem($activities);

        // contacts
        $contacts = new NavigationItem('content-navigation.contact.accounts.contacts');
        $contacts->setAction('contacts');
        $contacts->setId('contacts');
        $contacts->setContentType('account');
        $contacts->setContentComponent('accounts@sulucontact');
        $contacts->setContentComponentOptions(array('display'=>'contacts'));
        $contacts->setContentDisplay(array('edit'));
        $this->addNavigationItem($contacts);
        // financial infos
        $item = new NavigationItem('navigation.financials');
        $item->setAction('financials');
        $item->setId('financials');
        $item->setDisabled(true);
        $item->setContentType('account');
        $item->setContentComponent('accounts@sulucontact');
        $item->setContentComponentOptions(array('display'=>'financials'));
        $item->setContentDisplay(array('edit'));
        $this->addNavigationItem($item);
    }

    private function getViewForAccount() {

    }
}
