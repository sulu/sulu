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
use Sulu\Bundle\CoreBundle\Controller\AbstractRestController;

class ContactsController extends AbstractRestController
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
            $error = false;

            // Standard contact fields
            $contact->setFirstName($this->getRequest()->get('firstName'));
            $contact->setLastName($this->getRequest()->get('lastName'));

            $contact->setTitle($this->getRequest()->get('title'));
            $contact->setPosition($this->getRequest()->get('position'));

            $contact->setLocaleSystem($this->getRequest()->get('localeSystem'));

            $contact->setChanged(new DateTime());

            // process emails
            $success = $this->processEmail($contact)
                && $this->processPhone($contact);

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
    protected function processEmail(Contact $contact)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();

        $emails = $this->getRequest()->get('emails');
        if ($emails != null) {
            foreach ($contact->getEmails() as $contactEmail) {
                /** @var Email $contactEmail */
                $this->findMatch($emails, $contactEmail->getId(), $matchedEntry, $matchedKey);

                if ($matchedEntry == null) {
                    // delete email if it is not listed anymore
                    $contact->removeEmail($contactEmail);
                } else {
                    // update email if it is matched
                    $success = $this->updateEmail($contactEmail, $matchedEntry);
                    if (!$success) {
                        break;
                    }
                }

                // Remove done element from array
                if (!is_null($matchedKey)) {
                    unset($emails[$matchedKey]);
                }
            }

            // The emails which have not been delete or updated have to be added
            foreach ($emails as $email) {
                if (!$success) {
                    break;
                }
                $success = $this->addEmail($contact, $email, $em);
            }
        }

        return $success;
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
     * @param $email The email object to update
     * @param $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateEmail($email, $entry)
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
    protected function processPhone(Contact $contact)
    {
        $success = true;

        $phones = $this->getRequest()->get('phones');

        $delete = function ($phone) use ($contact)
        {
            return $contact->removePhone($phone);
        };

        $update = function ($phone)
        {
            return $this->updatePhone($phone, $matchedEntry);
        };

        $add = function ($phone) use ($contact)
        {
            return $this->addPhone($contact, $phone);
        };

        return $this->processPut($contact->getPhones(), $phones, $delete, $update);
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
     * @param $phone The phone object to update
     * @param $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updatePhone($phone, $entry)
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