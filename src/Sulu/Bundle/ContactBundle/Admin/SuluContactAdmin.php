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

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluContactAdmin extends Admin
{

    public function __construct($title)
    {
        $rootNavigationItem = new NavigationItem($title);

        $section = new NavigationItem('Tools');

        $contacts = new NavigationItem('Contacts');
        $contacts->setIcon('contact-book');
        $section->addChild($contacts);

        $people = new NavigationItem('People');
        $people->setIcon('parents');
        $people->setAction('contacts/contacts');
        $contacts->addChild($people);

        $companies = new NavigationItem('Companies');
        $companies->setIcon('bank');
        $companies->setAction('contacts/accounts');
        $contacts->addChild($companies);

        $rootNavigationItem->addChild($section);

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return array();
    }

}
