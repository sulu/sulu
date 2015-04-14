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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class SuluContactContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = array())
    {
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setGroups(array('contact'));
        $details->setComponent('contacts@sulucontact');
        $details->setComponentOptions(array('display'=>'form'));

        $documents = new ContentNavigationItem('content-navigation.contacts.documents');
        $documents->setAction('documents');
        $documents->setGroups(array('contact'));
        $documents->setComponent('contacts@sulucontact');
        $documents->setComponentOptions(array('display'=>'documents'));
        $documents->setDisplay(array('edit'));

        return array($details, $documents);
    }
}
