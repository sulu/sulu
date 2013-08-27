<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use DateTime;
use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Note;

class ContactsController extends FOSRestController
{
    /**
     * Lists all the contacts or filters the contacts by parameters
     * Special function for lists
     * route /contacts/list
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listContactsAction()
    {
        $listHelper = $this->get('sulu_core.list_rest_helper');

        $where = array();

        $codes = $listHelper->find('SuluContactBundle:Contact', $where);

        $response = array(
            'total' => sizeof($codes),
            'items' => $codes
        );
        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the contact with the given Id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getContactAction($id)
    {
        $contact = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Contact')
            ->find($id);

        $view = $this->view($contact, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new contact
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postContactsAction()
    {
        $firstName = $this->getRequest()->get('firstName');
        $lastName = $this->getRequest()->get('lastName');

        if ($firstName != null && $lastName != null) {
            $em = $this->getDoctrine()->getManager();

            $error = false;

            // Standard contact fields
            $contact = new Contact();
            $contact->setFirstName($firstName);
            $contact->setLastName($lastName);

            $contact->setTitle($this->getRequest()->get('title'));
            $contact->setPosition($this->getRequest()->get('position'));

            $contact->setLocaleSystem($this->getRequest()->get('localeSystem'));

            $contact->setCreated(new DateTime());
            $contact->setChanged(new DateTime());

            // Add email addresses, if no error has occured yet
            $emails = $this->getRequest()->get('emails');
            if (!$error && $emails != null) {
                foreach ($emails as $emailData) {
                    $emailType = $this->getDoctrine()
                        ->getRepository('SuluContactBundle:EmailType')
                        ->find($emailData['emailType']['id']);

                    if (!$emailType) {
                        $error = true;
                        $this->view(null, 400);
                        break;
                    } else {
                        $email = new Email();
                        $email->setEmail($emailData['email']);
                        $email->setEmailType($emailType);
                        $em->persist($email);
                        $contact->addEmail($email);
                    }
                }
            }

            // Add phones, if no error has occured yet
            $phones = $this->getRequest()->get('phones');
            if (!$error && $phones != null) {
                foreach ($phones as $phoneData) {
                    $phoneType = $this->getDoctrine()
                        ->getRepository('SuluContactBundle:PhoneType')
                        ->find($phoneData['phoneType']['id']);

                    if (!$phoneType) {
                        $error = true;
                        $this->view(null, 400);
                        break;
                    } else {
                        $phone = new Phone();
                        $phone->setPhone($phoneData['phone']);
                        $phone->setPhoneType($phoneType);
                        $em->persist($phone);
                        $contact->addPhone($phone);
                    }
                }
            }

            // Add addresses, if no error has occured yet
            $addresses = $this->getRequest()->get('addresses');
            if (!$error && $addresses != null) {
                foreach ($addresses as $addressData) {
                    $addressType = $this->getDoctrine()
                        ->getRepository('SuluContactBundle:AddressType')
                        ->find($addressData['addressType']['id']);

                    $country = $this->getDoctrine()
                        ->getRepository('SuluContactBundle:Country')
                        ->find($addressData['country']['id']);

                    if (!$addressType || !$country) {
                        $error = true;
                        $this->view(null, 400);
                        break;
                    } else {
                        $address = new Address();
                        $address->setStreet($addressData['street']);
                        $address->setNumber($addressData['number']);
                        $address->setZip($addressData['zip']);
                        $address->setCity($addressData['city']);
                        $address->setState($addressData['state']);
                        $address->setCountry($country);
                        $address->setAddressType($addressType);

                        // add additional fields
                        if (isset($addressData['addition'])) {
                            $address->setAddition($addressData['addition']);
                        }

                        $em->persist($address);
                        $contact->addAddresse($address);
                    }
                }
            }

            // Add notes, if no error has occured yet
            $notes = $this->getRequest()->get('notes');
            if (!$error && $notes != null) {
                foreach ($notes as $noteData) {
                    $note = new Note();
                    $note->setValue($noteData['value']);

                    $em->persist($note);
                    $contact->addNote($note);
                }
            }

            $em->persist($contact);

            if (!$error) {
                $em->flush();
                $view = $this->view($contact, 200);
            } else {
                $view = $this->view(null, 400);
            }
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }
}