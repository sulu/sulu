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

class ContactContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = [])
    {
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setPosition(10);
        $details->setComponent('contacts/edit/details@sulucontact');

        $documents = new ContentNavigationItem('content-navigation.contacts.documents');
        $documents->setAction('documents');
        $documents->setPosition(20);
        $documents->setComponent('documents-tab@sulucontact');
        $documents->setComponentOptions(['type' => 'contact']);
        $documents->setDisplay(['edit']);

        return [$details, $documents];
    }
}
