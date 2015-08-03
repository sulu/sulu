<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class AccountContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = [])
    {
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setId('details');
        $details->setComponent('accounts@sulucontact');
        $details->setComponentOptions(['display' => 'form']);

        $contacts = new ContentNavigationItem('content-navigation.contact.accounts.contacts');
        $contacts->setAction('contacts');
        $contacts->setId('contacts');
        $contacts->setComponent('accounts@sulucontact');
        $contacts->setComponentOptions(['display' => 'contacts']);
        $contacts->setDisplay(['edit']);

        $documents = new ContentNavigationItem('content-navigation.accounts.documents');
        $documents->setAction('documents');
        $documents->setComponent('accounts@sulucontact');
        $documents->setComponentOptions(['display' => 'documents-tab']);
        $documents->setDisplay(['edit']);

        return [$details, $contacts, $documents];
    }
}
