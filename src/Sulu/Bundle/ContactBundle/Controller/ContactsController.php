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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\CoreBundle\Controller\RestController;

/**
 * Makes contacts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class ContactsController extends RestController
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

        $contacts = $listHelper->find('SuluContactBundle:Contact', $where);

        $response = array(
            'total' => sizeof($contacts),
            'items' => $contacts
        );
        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Deletes a Contact with the given ID from database
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteContactAction($id)
    {
        /** @var Contact $contact */
        $contact = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Contact')
            ->find($id);

        if ($contact != null) {
            $em = $this->getDoctrine()->getManager();
            $addresses = $contact->getAddresses()->toArray();
            /** @var Address $address */
            foreach ($addresses as $address) {
                if ($address->getAccounts()->count() == 0 && $address->getContacts()->count() == 1) {
                    $em->remove($address);
                }
            }
            $phones = $contact->getPhones()->toArray();
            /** @var Phone $phone */
            foreach ($phones as $phone) {
                if ($phone->getAccounts()->count() == 0 && $phone->getContacts()->count() == 1) {
                    $em->remove($phone);
                }
            }
            $emails = $contact->getEmails()->toArray();
            /** @var Email $email */
            foreach ($emails as $email) {
                if ($email->getAccounts()->count() == 0 && $email->getContacts()->count() == 1) {
                    $em->remove($email);
                }
            }

            $em->remove($contact);
            $em->flush();

            $view = $this->view(null, 204);

        } else {
            $view = $this->view(null, 404);
        }

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
            if (!$error && !empty($emails)) {
                foreach ($emails as $emailData) {
                    $error = !$this->addEmail($contact, $emailData, $em);
                    if ($error) {
                        break;
                    }
                }
            }

            // Add phones, if no error has occured yet
            $phones = $this->getRequest()->get('phones');
            if (!$error && !empty($phones)) {
                foreach ($phones as $phoneData) {
                    $error = !$this->addPhone($contact, $phoneData, $em);
                    if ($error) {
                        break;
                    }
                }
            }

            // Add addresses, if no error has occured yet
            $addresses = $this->getRequest()->get('addresses');
            if (!$error && !empty($addresses)) {
                foreach ($addresses as $addressData) {
                    $error = !$this->addAddress($contact, $addressData, $em);
                    if ($error) {
                        break;
                    }
                }
            }

            // Add notes, if no error has occured yet
            $notes = $this->getRequest()->get('notes');
            if (!$error && !empty($notes)) {
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
        $em = $this->getDoctrine()->getManager();

        $contact = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Contact')
            ->find($id);

        if (!$contact) {
            $view = $this->view(null, 400);
        } else {
            $error = false;

            // Standard contact fields
            $contact->setFirstName($this->getRequest()->get('firstName'));
            $contact->setLastName($this->getRequest()->get('lastName'));

            $contact->setTitle($this->getRequest()->get('title'));
            $contact->setPosition($this->getRequest()->get('position'));

            $contact->setLocaleSystem($this->getRequest()->get('localeSystem'));

            $contact->setChanged(new DateTime());

            // process emails
            $success = $this->processEmails($contact)
                && $this->processPhones($contact)
                && $this->processAddresses($contact)
                && $this->processNotes($contact);

            if ($success) {
                $em->flush();
                $view = $this->view($contact, 200);
            } else {
                $view = $this->view(null, 400);
            }
        }

        return $this->handleView($view);
    }

    /**
     * Process all emails from request
     * @param Contact $contact The contact on which is worked
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processEmails(Contact $contact)
    {
        $emails = $this->getRequest()->get('emails');

        $delete = function ($email) use ($contact) {
            return $contact->removeEmail($email);
        };

        $update = function ($email, $matchedEntry) {
            return $this->updateEmail($email, $matchedEntry);
        };

        $add = function ($email) use ($contact) {
            return $this->addEmail($contact, $email);
        };

        return $this->processPut($contact->getEmails(), $emails, $delete, $update, $add);
    }

    /**
     * Adds a new email to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $emailData
     * @param ObjectManager $em
     * @return bool True if there was no error, otherwise false
     */
    protected function addEmail(Contact $contact, $emailData)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();

        $emailType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:EmailType')
            ->find($emailData['emailType']['id']);

        if (!$emailType || isset($emailData['id'])) {
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
     * Updates the given email address
     * @param Email $email The email object to update
     * @param $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateEmail(Email $email, $entry)
    {
        $success = true;

        $emailType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:EmailType')
            ->find($entry['emailType']['id']);

        if (!$emailType) {
            $success = false;
        } else {
            $email->setEmail($entry['email']);
            $email->setEmailType($emailType);
        }

        return $success;
    }

    /**
     * Process all phones from request
     * @param Contact $contact The contact on which is worked
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processPhones(Contact $contact)
    {
        $phones = $this->getRequest()->get('phones');

        $delete = function ($phone) use ($contact) {
            return $contact->removePhone($phone);
        };

        $update = function ($phone, $matchedEntry) {
            return $this->updatePhone($phone, $matchedEntry);
        };

        $add = function ($phone) use ($contact) {
            return $this->addPhone($contact, $phone);
        };

        return $this->processPut($contact->getPhones(), $phones, $delete, $update, $add);
    }

    /**
     * Add a new phone to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $phoneData
     * @param ObjectManager $em
     * @return bool True if there was no error, otherwise false
     */
    protected function addPhone(Contact $contact, $phoneData)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();

        $phoneType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:PhoneType')
            ->find($phoneData['phoneType']['id']);

        if (!$phoneType || isset($phoneData['id'])) {
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
     * Updates the given phone
     * @param Phone $phone The phone object to update
     * @param $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updatePhone(Phone $phone, $entry)
    {
        $success = true;

        $phoneType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:PhoneType')
            ->find($entry['phoneType']['id']);

        if (!$phoneType) {
            $success = false;
        } else {
            $phone->setPhone($entry['phone']);
            $phone->setPhoneType($phoneType);
        }

        return $success;
    }

    /**
     * Process all addresses from request
     * @param Contact $contact The contact on which is worked
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processAddresses(Contact $contact)
    {
        $addresses = $this->getRequest()->get('addresses');

        $delete = function ($address) use ($contact) {
            return $contact->removeAddresse($address);
        };

        $update = function ($address, $matchedEntry) {
            return $this->updateAddress($address, $matchedEntry);
        };

        $add = function ($address) use ($contact) {
            return $this->addAddress($contact, $address);
        };

        return $this->processPut($contact->getAddresses(), $addresses, $delete, $update, $add);
    }

    /**
     * Add a new address to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $addressData
     * @return bool True if there was no error, otherwise false
     */
    protected function addAddress(Contact $contact, $addressData)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();

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
     * Updates the given address
     * @param Address $address The phone object to update
     * @param $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateAddress(Address $address, $entry)
    {
        $success = true;

        $addressType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:AddressType')
            ->find($entry['addressType']['id']);

        $country = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Country')
            ->find($entry['addressType']['id']);

        if (!$addressType || !$country) {
            $success = false;
        } else {
            $address->setStreet($entry['street']);
            $address->setNumber($entry['number']);
            $address->setZip($entry['zip']);
            $address->setCity($entry['city']);
            $address->setState($entry['state']);
            $address->setCountry($country);
            $address->setAddressType($addressType);

            if (isset($entry['addition'])) {
                $address->setAddition($entry['addition']);
            }
        }

        return $success;
    }

    /**
     * Process all notes from request
     * @param Contact $contact The contact on which is worked
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processNotes(Contact $contact)
    {
        $notes = $this->getRequest()->get('notes');

        $delete = function ($note) use ($contact) {
            return $contact->removeNote($note);
        };

        $update = function ($note, $matchedEntry) {
            return $this->updateNote($note, $matchedEntry);
        };

        $add = function ($note) use ($contact) {
            return $this->addNote($contact, $note);
        };

        return $this->processPut($contact->getNotes(), $notes, $delete, $update, $add);
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $noteData
     * @param ObjectManager $em
     * @return bool True if there was no error, otherwise false
     */
    protected function addNote(Contact $contact, $noteData)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();

        $note = new Note();
        $note->setValue($noteData['value']);

        $em->persist($note);
        $contact->addNote($note);

        return $success;
    }

    /**
     * Updates the given note
     * @param Address $address The phone object to update
     * @param $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateNote(Note $note, $entry)
    {
        $success = true;

        $note->setValue($entry['value']);

        return $success;
    }
}