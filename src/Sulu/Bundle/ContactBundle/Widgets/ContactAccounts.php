<?php
/*
  * This file is part of the Sulu CMS.
  *
  * (c) MASSIVE ART WebServices GmbH
  *
  * This source file is subject to the MIT license that is bundled
  * with this source code in the file LICENSE.
  */

namespace Sulu\Bundle\ContactBundle\Widgets;

use Doctrine\ORM\PersistentCollection;
use Sulu\Bundle\AdminBundle\Widgets\WidgetInterface;
use Sulu\Bundle\AdminBundle\Widgets\WidgetParameterException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetEntityNotFoundException;
use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;

/**
 * Widget for all accounts of a contact
 *
 * @package Sulu\Bundle\ContactBundle\Widgets
 */
class ContactAccounts implements WidgetInterface
{
    protected $em;

    protected $widgetName = 'ContactAccounts';
    protected $contactEntityName = 'SuluContactBundle:Contact';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * return name of widget
     *
     * @return string
     */
    public function getName()
    {
        return 'contact-accounts';
    }

    /**
     * returns template name of widget
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'SuluContactBundle:Widgets:contact.accounts.html.twig';
    }

    /**
     * returns data to render template
     *
     * @param array $options
     * @throws WidgetEntityNotFoundException
     * @throws WidgetParameterException
     * @return array
     */
    public function getData($options)
    {
        if (!empty($options) &&
            array_key_exists('contact', $options) &&
            !empty($options['contact'])
        ) {
            $id = $options['contact'];
            $contact = $this->em->getRepository($this->contactEntityName)->findAccountsByContactId($id);

            if (!$contact) {
                throw new WidgetEntityNotFoundException(
                    'Entity ' . $this->contactEntityName . ' with id ' . $id . ' not found!',
                    $this->widgetName,
                    $id
                );
            }

            return $this->parseAccounts($contact->getAccountContacts());
        } else {
            throw new WidgetParameterException(
                'Required parameter contact not found or empty!',
                $this->widgetName,
                'contact'
            );
        }
    }

    /**
     * Parses the main account data
     *
     * @param PersistentCollection $accountsContact
     * @return array
     */
    protected function parseAccounts(PersistentCollection $accountsContact)
    {
        // TODO sort by main account?
        $length = count($accountsContact);
        if($length > 0) {
            $data = [];
            foreach($accountsContact as $accountContact) {
                $tmp = [];
                $tmp['id'] = $accountContact->getAccount()->getId();
                $tmp['name'] = $accountContact->getAccount()->getName();
                $tmp['phone'] = $accountContact->getAccount()->getMainPhone();
                $tmp['email'] = $accountContact->getAccount()->getMainEmail();
                $tmp['url'] = $accountContact->getAccount()->getMainUrl();
                $tmp['main'] = $accountContact->getMain();
                $data[] = $tmp;
            }
            return $data;
        }

        return null;
    }
}
