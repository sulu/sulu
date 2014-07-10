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
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;


/**
 * Makes accounts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
abstract class AbstractContactController extends RestController implements ClassResourceInterface
{
    /**
     * @return AbstractContactManager
     */
    abstract protected function getContactManager();

    /**
     * sets main address
     * @param $addresses
     * @return mixed
     */
    protected function checkAndSetMainAddress($addresses)
    {
        return $this->getContactManager()->setMainForCollection($addresses);
    }

    /**
     * sets Entity's Main-Email
     * @param Contact|Account $entity
     */
    protected function setMainEmail($entity)
    {
        // set main to first entry or to null
        if ($entity->getEmails()->isEmpty()) {
            $entity->setMainEmail(null);
        } else {
            $entity->setMainEmail($entity->getEmails()->first()->getEmail());
        }
    }

    /**
     * sets Entity's Main-Phone
     * @param Contact|Account $entity
     */
    protected function setMainPhone($entity)
    {
        // set main to first entry or to null
        if ($entity->getPhones()->isEmpty()) {
            $entity->setMainPhone(null);
        } else {
            $entity->setMainPhone($entity->getPhones()->first()->getPhone());
        }
    }

    /**
     * sets Entity's Main-Fax
     * @param Contact|Account $entity
     */
    protected function setMainFax($entity)
    {
        // set main to first entry or to null
        if ($entity->getFaxes()->isEmpty()) {
            $entity->setMainFax(null);
        } else {
            $entity->setMainFax($entity->getFaxes()->first()->getFax());
        }
    }

    /**
     * sets Entity's Main-Url
     * @param Contact|Account $entity
     */
    protected function setMainUrl($entity)
    {
        // set main to first entry or to null
        if ($entity->getUrls()->isEmpty()) {
            $entity->setMainUrl(null);
        } else {
            $entity->setMainUrl($entity->getUrls()->first()->getUrl());
        }
    }

    /**
     * adds new relations
     * @param $contact
     * @param Request $request
     * @param AbstractContactManager $contactManager
     */
    protected function addNewContactRelations($contact, Request $request, AbstractContactManager $contactManager)
    {
        // urls
        $urls = $request->get('urls');
        if (!empty($urls)) {
            foreach ($urls as $urlData) {
                $this->addUrl($contact, $urlData);
            }
            $this->setMainUrl($contact);
        }

        //faxes
        $faxes = $request->get('faxes');
        if (!empty($faxes)) {
            foreach ($faxes as $faxData) {
                $this->addFax($contact, $faxData);
            }
            $this->setMainFax($contact);
        }

        // emails
        $emails = $request->get('emails');
        if (!empty($emails)) {
            foreach ($emails as $emailData) {
                $this->addEmail($contact, $emailData);
            }
            $this->setMainEmail($contact);
        }

        // phones
        $phones = $request->get('phones');
        if (!empty($phones)) {
            foreach ($phones as $phoneData) {
                $this->addPhone($contact, $phoneData);
            }
            $this->setMainPhone($contact);
        }

        // addresses
        $addresses = $request->get('addresses');
        if (!empty($addresses)) {
            foreach ($addresses as $addressData) {
                $address = $this->createAddress($addressData, $isMain);
                $contactManager->addAddress($contact, $address, $isMain);
            }
        }

        // notes
        $notes = $request->get('notes');
        if (!empty($notes)) {
            foreach ($notes as $noteData) {
                $this->addNote($contact, $noteData);
            }
        }

        // handle tags
        $tags = $request->get('tags');
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $this->addTag($contact, $tag);
            }
        }
    }

    /**
     * Process all emails from request
     * @param $contact The contact on which is worked
     * @param $emails
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processEmails($contact, $emails)
    {
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
        $this->setMainEmail($contact);

        return $result;
    }

    /**
     * Adds a new email to the given contact and persist it with the given object manager
     * @param $contact
     * @param $emailData
     * @return bool
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addEmail($contact, $emailData)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();
        $emailEntity = 'SuluContactBundle:Email';
        $emailTypeEntity = 'SuluContactBundle:EmailType';

        $emailType = $this->getDoctrine()
            ->getRepository($emailTypeEntity)
            ->find($emailData['emailType']['id']);

        if (isset($emailData['id'])) {
            throw new EntityIdAlreadySetException($emailEntity, $emailData['id']);
        } elseif (!$emailType) {
            throw new EntityNotFoundException($emailTypeEntity, $emailData['emailType']['id']);
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
     * @param array $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws EntityNotFoundException
     */
    protected function updateEmail(Email $email, $entry)
    {
        $success = true;
        $emailTypeEntity = 'SuluContactBundle:EmailType';

        $emailType = $this->getDoctrine()
            ->getRepository($emailTypeEntity)
            ->find($entry['emailType']['id']);

        if (!$emailType) {
            throw new EntityNotFoundException($emailTypeEntity, $entry['emailType']['id']);
        } else {
            $email->setEmail($entry['email']);
            $email->setEmailType($emailType);
        }

        return $success;
    }


    /**
     * Process all urls of request
     * @param $contact The contact on which is processed
     * @param $urls
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUrls($contact, $urls)
    {
        $delete = function ($url) use ($contact) {
            return $contact->removeUrl($url);
        };

        $update = function ($url, $matchedEntry) {
            return $this->updateUrl($url, $matchedEntry);
        };

        $add = function ($url) use ($contact) {
            return $this->addUrl($contact, $url);
        };

        $result = $this->processPut($contact->getUrls(), $urls, $delete, $update, $add);
        // check main
        $this->setMainUrl($contact);

        return $result;
    }

    /**
     * @param Url $url
     * @param $entry
     * @return bool
     * @throws EntityNotFoundException
     */
    protected function updateUrl(Url $url, $entry)
    {
        $success = true;
        $urlTypeEntity = 'SuluContactBundle:UrlType';

        /** @var UrlType $urlType */
        $urlType = $this->getDoctrine()
            ->getRepository($urlTypeEntity)
            ->find($entry['urlType']['id']);

        if (!$urlType) {
            throw new EntityNotFoundException($urlTypeEntity, $entry['urlType']['id']);
        } else {
            $url->setUrl($entry['url']);
            $url->setUrlType($urlType);
        }

        return $success;
    }

    /**
     * Adds a new tag to the given contact
     * @param $contact
     * @param $data
     * @return bool
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addUrl($contact, $data)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();
        $urlEntity = 'SuluContactBundle:Url';
        $urlTypeEntity = 'SuluContactBundle:UrlType';

        $urlType = $this->getDoctrine()
            ->getRepository($urlTypeEntity)
            ->find($data['urlType']['id']);

        if (isset($data['id'])) {
            throw new EntityIdAlreadySetException($urlEntity, $data['id']);
        } elseif (!$urlType) {
            throw new EntityNotFoundException($urlTypeEntity, $data['urlType']['id']);
        } else {
            $url = new Url();
            $url->setUrl($data['url']);
            $url->setUrlType($urlType);
            $em->persist($url);
            $contact->addUrl($url);
        }

        return $success;
    }

    /**
     * Process all phones from request
     * @param $contact The contact on which is processed
     * @param $phones
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processPhones($contact, $phones)
    {
        $delete = function ($phone) use ($contact) {
            return $contact->removePhone($phone);
        };

        $update = function ($phone, $matchedEntry) {
            return $this->updatePhone($phone, $matchedEntry);
        };

        $add = function ($phone) use ($contact) {
            return $this->addPhone($contact, $phone);
        };

        $result = $this->processPut($contact->getPhones(), $phones, $delete, $update, $add);
        // check main
        $this->setMainPhone($contact);

        return $result;
    }

    /**
     * Add a new phone to the given contact and persist it with the given object manager
     * @param $contact
     * @param $phoneData
     * @return bool True if there was no error, otherwise false
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addPhone($contact, $phoneData)
    {
        $success = true;
        $em = $this->getDoctrine()->getManager();
        $phoneTypeEntity = 'SuluContactBundle:PhoneType';
        $phoneEntity = 'SuluContactBundle:Phone';

        $phoneType = $this->getDoctrine()
            ->getRepository($phoneTypeEntity)
            ->find($phoneData['phoneType']['id']);

        if (isset($phoneData['id'])) {
            throw new EntityIdAlreadySetException($phoneEntity, $phoneData['id']);
        } elseif (!$phoneType) {
            throw new EntityNotFoundException($phoneTypeEntity, $phoneData['phoneType']['id']);
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
     * @throws EntityNotFoundException
     */
    protected function updatePhone(Phone $phone, $entry)
    {
        $success = true;
        $phoneTypeEntity = 'SuluContactBundle:PhoneType';

        $phoneType = $this->getDoctrine()
            ->getRepository($phoneTypeEntity)
            ->find($entry['phoneType']['id']);

        if (!$phoneType) {
            throw new EntityNotFoundException($phoneTypeEntity, $entry['phoneType']['id']);
        } else {
            $phone->setPhone($entry['phone']);
            $phone->setPhoneType($phoneType);
        }

        return $success;
    }

    /**
     * @param $contact
     * @param $faxes
     * @return bool
     */
    protected function processFaxes($contact, $faxes)
    {
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

        $result = $this->processPut($contact->getFaxes(), $faxes, $delete, $update, $add);
        // check main
        $this->setMainFax($contact);

        return $result;
    }

    /**
     * @param $contact
     * @param $faxData
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function addFax($contact, $faxData)
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
     * Creates an address based on the data passed
     * @param $addressData
     * @param $isMain returns if address is main address
     * @return Address
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function createAddress($addressData, &$isMain = null)
    {
        $em = $this->getDoctrine()->getManager();
        $addressEntity = 'SuluContactBundle:Address';
        $addressTypeEntity = 'SuluContactBundle:AddressType';
        $countryEntity = 'SuluContactBundle:Country';

        $addressType = $this->getDoctrine()
            ->getRepository($addressTypeEntity)
            ->find($addressData['addressType']['id']);

        $country = $this->getDoctrine()
            ->getRepository($countryEntity)
            ->find($addressData['country']['id']);

        if (isset($addressData['id'])) {
            throw new EntityIdAlreadySetException($addressEntity, $addressData['id']);
        } elseif (!$country) {
            throw new EntityNotFoundException($countryEntity, $addressData['country']['id']);
        } elseif (!$addressType) {
            throw new EntityNotFoundException($addressTypeEntity, $addressData['addressType']['id']);
        } else {
            $address = new Address();
            $address->setStreet($addressData['street']);
            $address->setNumber($addressData['number']);
            $address->setZip($addressData['zip']);
            $address->setCity($addressData['city']);
            $address->setState($addressData['state']);

            if (isset($addressData['primaryAddress'])) {
                $isMain = $this->getBooleanValue($addressData['primaryAddress']);
            }
            if (isset($addressData['billingAddress'])) {
                $address->setBillingAddress($this->getBooleanValue($addressData['billingAddress']));
            }
            if (isset($addressData['deliveryAddress'])) {
                $address->setDeliveryAddress($this->getBooleanValue($addressData['deliveryAddress']));
            }
            if (isset($addressData['postboxCity'])) {
                $address->setPostboxCity($addressData['postboxCity']);
            }
            if (isset($addressData['postboxNumber'])) {
                $address->setPostboxNumber($addressData['postboxNumber']);
            }
            if (isset($addressData['postboxPostcode'])) {
                $address->setPostboxPostcode($addressData['postboxPostcode']);
            }

            $address->setCountry($country);
            $address->setAddressType($addressType);

            // add additional fields
            if (isset($addressData['addition'])) {
                $address->setAddition($addressData['addition']);
            }

            $em->persist($address);
        }
        return $address;
    }

    /**
     * Updates the given address
     * @param Address $address The phone object to update
     * @param mixed $entry The entry with the new data
     * @param Bool $isMain returns if address should be set to main
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function updateAddress(Address $address, $entry, &$isMain = null)
    {
        $success = true;
        $addressTypeEntity = 'SuluContactBundle:AddressType';
        $countryEntity = 'SuluContactBundle:Country';

        $addressType = $this->getDoctrine()
            ->getRepository($addressTypeEntity)
            ->find($entry['addressType']['id']);

        $country = $this->getDoctrine()
            ->getRepository($countryEntity)
            ->find($entry['country']['id']);

        if (!$addressType) {
            throw new EntityNotFoundException($addressTypeEntity, $entry['addressType']['id']);
        } else {
            if (!$country) {
                throw new EntityNotFoundException($countryEntity, $entry['country']['id']);
            } else {
                $address->setStreet($entry['street']);
                $address->setNumber($entry['number']);
                $address->setZip($entry['zip']);
                $address->setCity($entry['city']);
                $address->setState($entry['state']);
                $address->setCountry($country);
                $address->setAddressType($addressType);

                if (isset($entry['primaryAddress'])) {
                    $isMain = $this->getBooleanValue($entry['primaryAddress']);
                }
                if (isset($entry['billingAddress'])) {
                    $address->setBillingAddress($this->getBooleanValue($entry['billingAddress']));
                }
                if (isset($entry['deliveryAddress'])) {
                    $address->setDeliveryAddress($this->getBooleanValue($entry['deliveryAddress']));
                }
                if (isset($entry['postboxCity'])) {
                    $address->setPostboxCity($entry['postboxCity']);
                }
                if (isset($entry['postboxNumber'])) {
                    $address->setPostboxNumber($entry['postboxNumber']);
                }
                if (isset($entry['postboxPostcode'])) {
                    $address->setPostboxPostcode($entry['postboxPostcode']);
                }

                if (isset($entry['addition'])) {
                    $address->setAddition($entry['addition']);
                }
            }
        }

        return $success;
    }

    /**
     * Checks if a value is a boolean and converts it if necessary and returns it
     * @param $value
     * @return bool
     */
    protected function getBooleanValue($value){
        if(is_string($value)){
            return $value === 'true' ? true : false;
        } else if(is_bool($value)){
            return $value;
        } else if(is_numeric($value)){
            return $value === 1 ? true : false;
        }
    }

    /**
     * Process all notes from request
     * @param $contact The contact on which is worked
     * @param $notes
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processNotes($contact, $notes)
    {
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
     * @param $contact
     * @param $noteData
     * @return bool True if there was no error, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    protected function addNote($contact, $noteData)
    {
        $em = $this->getDoctrine()->getManager();
        $noteEntity = 'SuluContactBundle:Note';

        if (isset($noteData['id'])) {
            throw new EntityIdAlreadySetException($noteEntity, $noteData['id']);
        } else {
            $note = new Note();
            $note->setValue($noteData['value']);

            $em->persist($note);
            $contact->addNote($note);
        }

        return true;
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

    /**
     * Process all tags of request
     * @param $contact The contact on which is worked
     * @param $tags
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processTags($contact, $tags)
    {
        $delete = function ($tag) use ($contact) {
            return $contact->removeTag($tag);
        };

        $update = function () {
            return true;
        };

        $add = function ($tag) use ($contact) {
            return $this->addTag($contact, $tag);
        };

        return $this->processPut($contact->getTags(), $tags, $delete, $update, $add);
    }

    /**
     * Adds a new tag to the given contact and persist it with the given object manager
     * @param $contact
     * @param $data
     * @return bool True if there was no error, otherwise false
     */
    protected function addTag($contact, $data)
    {
        $success = true;
        $tagManager = $this->get('sulu_tag.tag_manager');
        $resolvedTag = $tagManager->findByName($data);
        $contact->addTag($resolvedTag);

        return $success;
    }

    /**
     * Process all bankAccounts of a request
     * @param $contact
     * @param $bankAccounts
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processBankAccounts($contact, $bankAccounts)
    {
        $delete = function ($bankAccounts) use ($contact) {
            $contact->removeBankAccount($bankAccounts);
            return true;
        };

        $update = function ($bankAccounts, $matchedEntry) {
            return $this->updateBankAccount($bankAccounts, $matchedEntry);
        };

        $add = function ($bankAccounts) use ($contact) {
            return $this->addBankAccount($contact, $bankAccounts);
        };

        return $this->processPut($contact->getBankAccounts(), $bankAccounts, $delete, $update, $add);
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager
     * @param $contact
     * @param $data
     * @return bool
     * @throws EntityIdAlreadySetException
     */
    protected function addBankAccount($contact, $data)
    {
        $em = $this->getDoctrine()->getManager();
        $entityName = 'SuluContactBundle:BankAccount';

        if (isset($data['id'])) {
            throw new EntityIdAlreadySetException($entityName, $data['id']);
        } else {
            $entity = new BankAccount();
            $entity->setBankName($data['bankName']);
            $entity->setBic($data['bic']);
            $entity->setIban($data['iban']);
            $entity->setPublic($data['public']);

            $em->persist($entity);
            $contact->addBankAccount($entity);
        }

        return true;
    }

    /**
     * Updates the given note
     * @param BankAccount $entity The phone object to update
     * @param string $data The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateBankAccount(BankAccount $entity, $data)
    {
        $success = true;

        $entity->setBankName($data['bankName']);
        $entity->setBic($data['bic']);
        $entity->setIban($data['iban']);
        $entity->setPublic($this->getBooleanValue($data['public']));

        return $success;
    }

    /**
     * Process all addresses from request
     * @param $contact The contact on which is worked
     * @param $addresses
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processAddresses($contact, $addresses)
    {
        $getAddressId = function($addressRelation) {
            return $addressRelation->getAddress()->getId();
        };

        $delete = function ($addressRelation) use ($contact) {
            $this->getContactManager()->removeAddressRelation($contact, $addressRelation);
            return true;
        };

        $update = function ($addressRelation, $matchedEntry) use ($contact) {
            $address = $addressRelation->getAddress();
            $result = $this->updateAddress($address, $matchedEntry, $isMain);
            if ($isMain) {
                $this->getContactManager()->unsetMain($this->getContactManager()->getAddressRelations($contact));
            }
            $addressRelation->setMain($isMain);

            return $result;
        };

        $add = function ($addressData) use ($contact) {
            $address = $this->createAddress($addressData, $isMain);
            $this->getContactManager()->addAddress($contact, $address, $isMain);
            return true;
        };

        $result = $this->processPut($this->getContactManager()->getAddressRelations($contact), $addresses, $delete, $update, $add, $getAddressId);

        // check if main exists, else take first address
        $this->checkAndSetMainAddress($this->getContactManager()->getAddressRelations($contact));

        return $result;
    }
}
