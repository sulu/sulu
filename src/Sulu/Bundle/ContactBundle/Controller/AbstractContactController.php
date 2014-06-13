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
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;


/**
 * Makes accounts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class AbstractContactController extends RestController implements ClassResourceInterface
{

    /**
     * checks if a primary email exists
     * @param $emails
     * @return mixed
     */
    protected function hasMainEmail($emails) {
        return $this->checkMainExistence($emails);
    }

    /**
     * checks if a primary phone exists
     * @param $phones
     * @return mixed
     */
    protected function hasMainPhone($phones) {
        return $this->checkMainExistence($phones);
    }

    /**
     * checks if a primary phone exists
     * @param $urls
     * @return mixed
     */
    protected function hasMainUrl($urls) {
        return $this->checkMainExistence($urls);
    }

    /**
     * checks if a primary phone exists
     * @param $faxes
     * @return mixed
     */
    protected function hasMainFax($faxes) {
        return $this->checkMainExistence($faxes);
    }

    /**
     * checks if a collection for main attribute
     * @param $arrayCollection
     * @return mixed
     */
    private function checkMainExistence($arrayCollection) {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->exists(function($index, $entity) {
                return $entity->getMain() === true;
            });
        }
        return false;
    }

    /**
     * sets the first element to main, if none is set
     * @param $arrayCollection
     */
    private function setMainForCollection($arrayCollection) {
        if (!$arrayCollection->isEmpty() && !$this->checkMainExistence($arrayCollection)) {
            $arrayCollection->first()->setMain(true);
        }
    }

    /**
     * checks if entity has main email or sets one
     * @param $emails
     */
    protected function checkAndSetMainEmail($emails) {
       $this->setMainForCollection($emails);
    }

    /**
     * Process all emails from request
     * @param $contact The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processEmails($contact, Request $request)
    {
        $emails = $request->get('emails');

        $delete = function ($email) use ($contact) {
            return $contact->removeEmail($email);
        };

        $update = function ($email, $matchedEntry) {
            return $this->updateEmail($email, $matchedEntry);
        };

        $add = function ($email) use ($contact) {
            return $this->addEmail($contact, $email);
        };

        $result = $this->processPut($contact->getEmails(), $emails, $delete, $update, $add);

        // check main
        $this->checkAndSetMainEmail($contact->getEmails());

        return $result;
    }

    /**
     * Adds a new email to the given contact and persist it with the given object manager
     * @param Contact $contact
     * @param $emailData
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
            $email->setMain(false);
            $em->persist($email);
            $contact->addEmail($email);
        }

        return $success;
    }

    /**
     * Updates the given email address
     * @param Email $email The email object to update
     * @param array $entry The entry with the new data
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
     * Process all urls of request
     * @param Contact $contact The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUrls(Contact $contact, Request $request)
    {
        $urls = $request->get('urls');

        $delete = function ($url) use ($contact) {
            return $contact->removeUrl($url);
        };

        $update = function ($url, $matchedEntry) {
            return $this->updateUrl($url, $matchedEntry);
        };

        $add = function ($url) use ($contact) {
            return $this->addUrl($contact, $url);
        };

        return $this->processPut($contact->getUrls(), $urls, $delete, $update, $add);
    }

    /**
     * Updates the given url
     * @param Url $url The phone object to update
     * @param $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateUrl(Url $url, $entry)
    {
        $success = true;

        $urlType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:UrlType')
            ->find($entry['urlType']['id']);

        if (!$urlType) {
            $success = false;
        } else {
            $url->setUrl($entry['url']);
            $url->setUrlType($urlType);
        }

        return $success;
    }

    /**
     * Adds a new tag to the given contact
     * @param Contact $contact
     * @param $data
     * @return bool True if there was no error, otherwise false
     */
    protected function addUrl(Contact $contact, $data)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();

        $urlType = $this->getDoctrine()
            ->getRepository('SuluContactBundle:UrlType')
            ->find($data['urlType']['id']);

        if (!$urlType || isset($data['id'])) {
            $success = false;
        } else {
            $url = new Url();
            $url->setMain(false);
            $url->setUrl($data['url']);
            $url->setUrlType($urlType);
            $em->persist($url);
            $contact->addUrl($url);
        }

        return $success;
    }

    /**
     * Process all phones from request
     * @param Contact $contact The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processPhones(Contact $contact, Request $request)
    {
        $phones = $request->get('phones');

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
            $phone->setMain(false);
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
     * @param Contact $contact
     * @param Request $request
     * @return bool
     */
    protected function processFaxes(Contact $contact, Request $request)
    {
        $faxes = $request->get('faxes');

        $delete = function ($fax) use ($contact) {
            $contact->removeFax($fax);

            return true;
        };

        $update = function ($fax, $matchedEntry) {
            return $this->updateFax($fax, $matchedEntry);
        };

        $add = function ($fax) use ($contact) {
            $this->addFax($contact, $fax);

            return true;
        };

        return $this->processPut($contact->getFaxes(), $faxes, $delete, $update, $add);
    }

    /**
     * @param Contact $contact
     * @param $faxData
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addFax(Contact $contact, $faxData)
    {
        $em = $this->getDoctrine()->getManager();
        $faxEntity = 'SuluContactBundle:Fax';
        $faxTypeEntity = 'SuluContactBundle:FaxType';

        $faxType = $this->getDoctrine()
            ->getRepository($faxTypeEntity)
            ->find($faxData['faxType']['id']);

        if (isset($faxData['id'])) {
            throw new EntityIdAlreadySetException($faxEntity, $faxData['id']);
        } elseif (!$faxType) {
            throw new EntityNotFoundException($faxTypeEntity, $faxData['faxType']['id']);
        } else {
            $fax = new Fax();
            $fax->setMain(false);
            $fax->setFax($faxData['fax']);
            $fax->setFaxType($faxType);
            $em->persist($fax);
            $contact->addFax($fax);
        }
    }

    /**
     * @param Fax $fax
     * @param $entry
     * @return bool
     * @throws EntityNotFoundException
     */
    protected function updateFax(Fax $fax, $entry)
    {
        $success = true;
        $faxTypeEntity = 'SuluContactBundle:FaxType';

        $faxType = $this->getDoctrine()
            ->getRepository($faxTypeEntity)
            ->find($entry['faxType']['id']);

        if (!$faxType) {
            throw new EntityNotFoundException($faxTypeEntity, $entry['faxType']['id']);
        } else {
            $fax->setFax($entry['fax']);
            $fax->setFaxType($faxType);
        }

        return $success;
    }

    /**
     * Process all addresses from request
     * @param Contact $contact The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processAddresses(Contact $contact, Request $request)
    {
        $addresses = $request->get('addresses');

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
     * @param array $entry The entry with the new data
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
            ->find($entry['country']['id']);

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
     * @param Request $request
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processNotes(Contact $contact, Request $request)
    {
        $notes = $request->get('notes');

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
     * @param Note $note
     * @param array $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateNote(Note $note, $entry)
    {
        $success = true;

        $note->setValue($entry['value']);

        return $success;
    }

}
