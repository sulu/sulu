<?php

/*
 * This file is part of Sulu.
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
        $details->setPosition(10);
        $details->setComponent('accounts/edit/details@sulucontact');

        $contacts = new ContentNavigationItem('content-navigation.contact.accounts.contacts');
        $contacts->setAction('contacts');
        $contacts->setPosition(20);
        $contacts->setComponent('accounts/edit/contacts@sulucontact');
        $contacts->setDisplay(['edit']);

        $documents = new ContentNavigationItem('content-navigation.accounts.documents');
        $documents->setAction('documents');
        $documents->setPosition(30);
        $documents->setComponent('documents-tab@sulucontact');
        $documents->setDisplay(['edit']);
        $documents->setComponentOptions(['type' => 'account']);

        return [$details, $contacts, $documents];
    }
}
