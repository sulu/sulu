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

class ContactContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = [])
    {
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setComponent('contacts@sulucontact');
        $details->setComponentOptions(['display' => 'form']);

        $documents = new ContentNavigationItem('content-navigation.contacts.documents');
        $documents->setAction('documents');
        $documents->setComponent('contacts@sulucontact');
        $documents->setComponentOptions(['display' => 'documents-tab']);
        $documents->setDisplay(['edit']);

        return [$details, $documents];
    }
}
