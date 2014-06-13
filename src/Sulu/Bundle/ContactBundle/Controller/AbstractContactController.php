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
        if ($arrayCollection && !$arrayCollection->isEmpty() && !$this->checkMainExistence($arrayCollection)) {
            $arrayCollection->first()->setMain(true);
        }
    }

    /** unsets main of all elements of arraycollection
     * @param $arrayCollection
     * @return boolean returns true if a element was unset
     */
    private function unsetMain($arrayCollection) {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->forAll(
                function($index, $entry) {
                    if ($entry->getMain() === true) {
                        $entry->setMain(false);
                        return false;
                    }
                    return true;
                }
            );
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
     * checks if entity has main phone or sets one
     * @param $phones
     */
    protected function checkAndSetMainPhone($phones) {
       $this->setMainForCollection($phones);
    }

    /**
     * checks if entity has main fax or sets one
     * @param $faxes
     */
    protected function checkAndSetMainFax($faxes) {
       $this->setMainForCollection($faxes);
    }

    /**
     * checks if entity has main url or sets one
     * @param $urls
     */
    protected function checkAndSetMainUrl($urls) {
       $this->setMainForCollection($urls);
    }

    /**
     * adds new relations
     * @param Contact|Account $contact
     * @param Request $request
     */
    protected function addNewContactRelations($contact, Request $request)
    {
        // urls
        $urls = $request->get('urls');
        if (!empty($urls)) {
            foreach ($urls as $urlData) {
                $this->addUrl($contact, $urlData);
            }
            $this->checkAndSetMainUrl($contact->getUrls());
        }

        //faxes
        $faxes = $request->get('faxes');
        if (!empty($faxes)) {
            foreach ($faxes as $faxData) {
                $this->addFax($contact, $faxData);
            }
            $this->checkAndSetMainFax($contact->getFaxes());
        }

        // emails
        $emails = $request->get('emails');
        if (!empty($emails)) {
            foreach ($emails as $emailData) {
                $this->addEmail($contact, $emailData);
            }
            $this->checkAndSetMainEmail($contact->getEmails());
        }

        // phones
        $phones = $request->get('phones');
        if (!empty($phones)) {
            foreach ($phones as $phoneData) {
                $this->addPhone($contact, $phoneData);
            }
            $this->checkAndSetMainPhone($contact->getPhones());
        }

        // addresses
        $addresses = $request->get('addresses');
        if (!empty($addresses)) {
            foreach ($addresses as $addressData) {
                $this->addAddress($contact, $addressData);
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
        $this->checkAndSetMainEmail($contact->getEmails());

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

            $main = false;
            if (array_key_exists('main', $emailData) && $emailData['main'] == true) {
                $main = true;
                $this->unsetMain($contact->getEmails());
            }
            $email->setMain($main);

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
        $this->checkAndSetMainUrl($contact->getUrls());

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
            $main = false;
            if (array_key_exists('main', $data) && $data['main'] == true) {
                $main = true;
                $this->unsetMain($contact->getUrls());
            }
            $url->setMain($main);
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
        $this->checkAndSetMainPhone($contact->getPhones());

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
            $main = false;
            if (array_key_exists('main', $phoneData) && $phoneData['main'] == true) {
                $main = true;
                $this->unsetMain($contact->getPhones());
            }
            $phone->setMain($main);
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
        $this->checkAndSetMainFax($contact->getFaxes());

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
            $main = false;
            if (array_key_exists('main', $faxData) && $faxData['main'] == true) {
                $main = true;
                $this->unsetMain($contact->getFaxes());
            }
            $fax->setMain($main);
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
     * @param $contact The contact on which is worked
     * @param $addresses
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processAddresses($contact, $addresses)
    {
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
     * @param $contact
     * @param $addressData
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function addAddress($contact, $addressData)
    {
        $result = true;
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
            $address->setCountry($country);
            $address->setAddressType($addressType);

            // add additional fields
            if (isset($addressData['addition'])) {
                $address->setAddition($addressData['addition']);
            }

            $em->persist($address);

            $contact->addAddresse($address);
        }
        return $result;
    }

    /**
     * Updates the given address
     * @param Address $address The phone object to update
     * @param array $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function updateAddress(Address $address, $entry)
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

                if (isset($entry['addition'])) {
                    $address->setAddition($entry['addition']);
                }
            }
        }

        return $success;
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
        $entity->setPublic($data['public']);

        return $success;
    }
}
