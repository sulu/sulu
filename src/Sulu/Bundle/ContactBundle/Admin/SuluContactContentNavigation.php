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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluContactContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        $this->setName('Contacts');

        /* CONTACTS */

        // details
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setGroups(array('contact'));
        $details->setComponent('contacts@sulucontact');
        $details->setComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);

        // activities
        $activities = new ContentNavigationItem('content-navigation.contacts.activities');
        $activities->setAction('activities');
        $activities->setGroups(array('contact'));
        $activities->setComponent('contacts@sulucontact');
        $activities->setComponentOptions(array('display'=>'activities'));
        $activities->setDisplay(array('edit'));
        $this->addNavigationItem($activities);

        // documents
        $documents = new ContentNavigationItem('content-navigation.contacts.documents');
        $documents->setAction('documents');
        $documents->setGroups(array('contact'));
        $documents->setComponent('contacts@sulucontact');
        $documents->setComponentOptions(array('display'=>'documents'));
        $documents->setDisplay(array('edit'));
        $this->addNavigationItem($documents);

        /* ACCOUNTS */

        // details
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setId('details');
        $details->setGroups(array('account'));
        $details->setComponent('accounts@sulucontact');
        $details->setComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);

        // activities
        $activities = new ContentNavigationItem('content-navigation.contacts.activities');
        $activities->setAction('activities');
        $activities->setGroups(array('account'));
        $activities->setComponent('accounts@sulucontact');
        $activities->setComponentOptions(array('display'=>'activities'));
        $activities->setDisplay(array('edit'));
        $this->addNavigationItem($activities);

        // contacts
        $contacts = new ContentNavigationItem('content-navigation.contact.accounts.contacts');
        $contacts->setAction('contacts');
        $contacts->setId('contacts');
        $contacts->setGroups(array('account'));
        $contacts->setComponent('accounts@sulucontact');
        $contacts->setComponentOptions(array('display'=>'contacts'));
        $contacts->setDisplay(array('edit'));
        $this->addNavigationItem($contacts);

        // financial infos
        $item = new ContentNavigationItem('navigation.financials');
        $item->setAction('financials');
        $item->setId('financials');
        $item->setDisabled(true);
        $item->setGroups(array('account'));
        $item->setComponent('accounts@sulucontact');
        $item->setComponentOptions(array('display'=>'financials'));
        $item->setDisplay(array('edit'));
        $this->addNavigationItem($item);

        // documents
        $documents = new ContentNavigationItem('content-navigation.accounts.documents');
        $documents->setAction('documents');
        $documents->setGroups(array('account'));
        $documents->setComponent('accounts@sulucontact');
        $documents->setComponentOptions(array('display'=>'documents'));
        $documents->setDisplay(array('edit'));
        $this->addNavigationItem($documents);
    }
}
