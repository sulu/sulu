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

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\AdminBundle\Widgets\WidgetException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\AdminBundle\Widgets\WidgetParameterException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetEntityNotFoundException;

/**
 * example widget for contact controller
 *
 * @package Sulu\Bundle\ContactBundle\Widgets
 */
class ContactInfo implements WidgetInterface
{
    protected $em;

    protected $widgetName = 'ContactInfo';
    protected $contactEntityName = 'SuluContactBundle:Contact';

    function __construct(EntityManager $em)
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
        return 'contact-info';
    }

    /**
     * returns template name of widget
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'SuluContactBundle:Widgets:contact.info.html.twig';
    }

    /**
     * returns data to render template
     *
     * @param array $options
     * @throws WidgetException
     * @return array
     */
    public function getData($options)
    {
        if (!empty($options) &&
            array_key_exists('contact', $options) &&
            !empty($options['contact'])
        ) {
            $id = $options['contact'];
            $contact = $this->em->getRepository(
                $this->contactEntityName
            )->find($id);

            if (!$contact) {
                throw new WidgetEntityNotFoundException(
                    'Entity ' . $this->contactEntityName . ' with id ' . $id . ' not found!',
                    $this->widgetName,
                    $id
                );
            }
            return $this->parseContactForListSidebar($contact);
        } else {
            throw new WidgetParameterException(
                'Required parameter contact not found or empty!',
                $this->widgetName,
                'contact'
            );
        }
    }

    /**
     * Returns the data neede for the contact list-sidebar
     *
     * @param Contact $contact
     * @return array
     */
    protected function parseContactForListSidebar(Contact $contact)
    {
        $data = [];

        $data['id'] = $contact->getId();
        $data['fullName'] = $contact->getFullName();

        if ($contact->getTitle()) {
            $data['title'] = $contact->getTitle()->getTitle();
        }
        if ($contact->getPosition()) {
            $data['position'] = $contact->getPosition()->getPosition();
        }

        $data['phone'] = $contact->getMainPhone();
        $data['email'] = $contact->getMainEmail();
        $data['fax'] = $contact->getMainFax();
        $data['url'] = $contact->getMainUrl();

        /* @var Address $contactAddress */
        $contactAddress = $contact->getMainAddress();

        if (!!$contactAddress) {
            $data['address']['street'] = $contactAddress->getStreet();
            $data['address']['number'] = $contactAddress->getNumber();
            $data['address']['zip'] = $contactAddress->getZip();
            $data['address']['city'] = $contactAddress->getCity();
            $data['address']['country'] = $contactAddress->getCountry(
            )->getName();
        }

        if (!!$contact->getMainAccount()) {
            $mainAccount = $contact->getMainAccount();
            $data['company']['id'] = $mainAccount->getId();
            $data['company']['name'] = $mainAccount->getName();
            $data['company']['email'] = $mainAccount->getMainEmail();

            /* @var Address $accountAddress */
            $accountAddress = $mainAccount->getMainAddress();

            if (!!$accountAddress) {
                $data['company']['address']['city'] = $accountAddress
                    ->getCity();
                $data['company']['address']['country'] = $accountAddress
                    ->getCountry()->getName();
            }
        }
        return $data;
    }
}
