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
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ContactAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker, $title)
    {
        $this->securityChecker = $securityChecker;

        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('');

        $contacts = new NavigationItem('navigation.contacts');
        $contacts->setIcon('user');

        if ($this->securityChecker->hasPermission('sulu.contact.people', 'view')) {
            $people = new NavigationItem('navigation.contacts.people');
            $people->setIcon('users');
            $people->setAction('contacts/contacts');
            $contacts->addChild($people);
        }

        if ($this->securityChecker->hasPermission('sulu.contact.organizations', 'view')) {
            $companies = new NavigationItem('navigation.contacts.companies');
            $companies->setIcon('building');
            $companies->setAction('contacts/accounts');
            $contacts->addChild($companies);
        }

        if ($contacts->hasChildren()) {
            $rootNavigationItem->addChild($section);
            $section->addChild($contacts);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulucontact';
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return array(
            'Sulu' => array(
                'Contacts' => array(
                    'sulu.contact.people',
                    'sulu.contact.organizations',
                ),
            ),
        );
    }
}
