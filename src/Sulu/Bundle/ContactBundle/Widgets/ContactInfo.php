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
use Sulu\Bundle\AdminBundle\Widgets\WidgetInterface;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Address;

/**
 * example widget for contact controller
 *
 * @package Sulu\Bundle\ContactBundle\Widgets
 */
class ContactInfo implements WidgetInterface
{
    protected $em;
    protected $contactManager;
    protected $accountManager;

    protected $contactEntityName = 'SuluContactBundle:Contact';

    function __construct(
        EntityManager $em,
        ContactManager $contactManager,
        AccountManager $accountManager
    )
    {
        $this->em = $em;
        $this->contactManager = $contactManager;
        $this->accountManager = $accountManager;
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
     * @return array
     */
    public function getData($options)

    {
        $errorMessage = array(
            'errorMessage' => 'Invalid contact id !'
        );

        if (!empty($options)) {

            if (array_key_exists('contact', $options)) {
                $id = $options['contact'];
                $contact = $this->em->getRepository(
                    $this->contactEntityName
                )->find($id);

                if (!$contact) {
                    return $errorMessage;
                }

                return $this->parseContactForListSidebar($contact);
            }
        }

        return $errorMessage;
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
        $data['title'] = $contact->getTitle()->getTitle();
        $data['position'] = $contact->getPosition()->getPosition();
        $data['phone'] = $contact->getMainPhone();
        $data['email'] = $contact->getMainEmail();
        $data['fax'] = $contact->getMainFax();
        $data['url'] = $contact->getMainUrl();

        /* @var Address $contactAddress */
        $contactAddress = $this->contactManager->getMainAddress($contact);

        if (!!$contactAddress) {
            $data['address']['street'] = $contactAddress->getStreet();
            $data['address']['number'] = $contactAddress->getNumber();
            $data['address']['zip'] = $contactAddress->getZip();
            $data['address']['city'] = $contactAddress->getCity();
            $data['address']['country'] = $contactAddress->getCountry(
            )->getName();
        }

        if (!!$contact->getMainAccount()) {
            $data['company']['name'] = $contact->getMainAccount()->getName();
            $data['company']['email'] = $contact->getMainAccount()
                ->getMainEmail();

            /* @var Address $accountAddress */
            $accountAddress = $this->accountManager->getMainAddress(
                $contact->getMainAccount()
            );

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
