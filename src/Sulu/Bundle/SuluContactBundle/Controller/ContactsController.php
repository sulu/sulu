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
use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Note;

class ContactsController extends FOSRestController
{
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
                    $error = !$this->addEmail($contact, $emailData, $em);
                    if ($error) {
                        break;
                    }
                }
            }

            // Add phones, if no error has occured yet
            $phones = $this->getRequest()->get('phones');
            if (!$error && $phones != null) {
                foreach ($phones as $phoneData) {
                    $error = !$this->addPhone($contact, $phoneData, $em);
                    if ($error) {
                        break;
                    }
                }
            }

            // Add addresses, if no error has occured yet
            $addresses = $this->getRequest()->get('addresses');
            if (!$error && $addresses != null) {
                foreach ($addresses as $addressData) {
                    $error = !$this->addAddress($contact, $addressData, $em);
                    if ($error) {
                        break;
                    }
                }
            }

            // Add notes, if no error has occured yet
            $notes = $this->getRequest()->get('notes');
            if (!$error && $notes != null) {
                foreach ($notes as $noteData) {
                    $error = !$this->addNote($contact, $noteData, $em);
                    if ($error) {
                        break;
                    }
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

    /**
     * Edits the existing contact with the given id
     * @param integer $id The id of the contact to update
     */
    public function putContactsAction($id)
    {
        $contact = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Contact')
            ->find($id);

        if (!$contact) {
            $view = $this->view(null, 400);
        } else {
            $em = $this->getDoctrine()->getManager();
            $error = false;

            // Standard contact fields
            $contact->setFirstName($this->getRequest()->get('firstName'));
            $contact->setLastName($this->getRequest()->get('lastName'));

            $contact->setTitle($this->getRequest()->get('title'));
            $contact->setPosition($this->getRequest()->get('position'));

            $contact->setLocaleSystem($this->getRequest()->get('localeSystem'));

            $contact->setChanged(new DateTime());


            $em->flush();

            $view = $this->view($contact, 200);
        }

        return $this->handleView($view);
    }

    /**
     * Adds a new email to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $emailData
     * @param ObjectManager $em
     * @return bool True if there was no error, otherwise false
     */
    protected function addEmail(Contact $contact, $emailData, ObjectManager $em)
    {
        $success = true;

        $emailType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:EmailType')
            ->find($emailData['emailType']['id']);

        if (!$emailType) {
            $success = false;
        } else {
            $email = new Email();
            $email->setEmail($emailData['email']);
            $email->setEmailType($emailType);
            $em->persist($email);
            $contact->addEmail($email);
        }

        return $success;
    }

    /**
     * Add a new phone to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $phoneData
     * @param ObjectManager $em
     * @return bool True if there was no error, otherwise false
     */
    protected function addPhone(Contact $contact, $phoneData, ObjectManager $em)
    {
        $success = true;

        $phoneType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:PhoneType')
            ->find($phoneData['phoneType']['id']);

        if (!$phoneType) {
            $success = false;
        } else {
            $phone = new Phone();
            $phone->setPhone($phoneData['phone']);
            $phone->setPhoneType($phoneType);
            $em->persist($phone);
            $contact->addPhone($phone);
        }

        return $success;
    }

    /**
     * Add a new address to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $addressData
     * @param ObjectManager $em
     * @return bool True if there was no error, otherwise false
     */
    protected function addAddress(Contact $contact, $addressData, ObjectManager $em)
    {
        $success = true;

        $addressType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:AddressType')
            ->find($addressData['addressType']['id']);

        $country = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Country')
            ->find($addressData['country']['id']);

        if (!$addressType || !$country) {
            $success = false;
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

        return $success;
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $noteData
     * @param ObjectManager $em
     * @return bool True if there was no error, otherwise false
     */
    protected function addNote(Contact $contact, $noteData, ObjectManager $em)
    {
        $success = true;

        $note = new Note();
        $note->setValue($noteData['value']);

        $em->persist($note);
        $contact->addNote($note);

        return $success;
    }
}