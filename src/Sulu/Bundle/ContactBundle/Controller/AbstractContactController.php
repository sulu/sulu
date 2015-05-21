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
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\BankAccount as BankAccountEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\ContactBundle\Entity\UrlType as UrlTypeEntity;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes accounts available through a REST API.
 */
abstract class AbstractContactController extends RestController implements ClassResourceInterface
{
    protected static $positionEntityName = 'SuluContactBundle:Position';
    protected static $mediaEntityName = 'SuluMediaBundle:Media';

    /**
     * @return AbstractContactManager
     */
    abstract protected function getContactManager();

    /**
     * sets main address.
     *
     * @param $addresses
     *
     * @return mixed
     */
    protected function checkAndSetMainAddress($addresses)
    {
        $this->getContactManager()->setMainForCollection($addresses);
    }

    /**
     * @return RestHelperInterface
     */
    protected function getRestHelper()
    {
        return $this->get('sulu_core.doctrine_rest_helper');
    }

    /**
     * adds new relations.
     *
     * @param ContactEntity $contact
     * @param Request $request
     */
    protected function addNewContactRelations($contact, Request $request)
    {
        $contactManager = $this->getContactManager();

        // urls
        $urls = $request->get('urls');
        if (!empty($urls)) {
            foreach ($urls as $urlData) {
                $this->addUrl($contact, $urlData);
            }
            $this->getContactManager()->setMainUrl($contact);
        }

        //faxes
        $faxes = $request->get('faxes');
        if (!empty($faxes)) {
            foreach ($faxes as $faxData) {
                $this->addFax($contact, $faxData);
            }
            $this->getContactManager()->setMainFax($contact);
        }

        // emails
        $emails = $request->get('emails');
        if (!empty($emails)) {
            foreach ($emails as $emailData) {
                $this->addEmail($contact, $emailData);
            }
            $this->getContactManager()->setMainEmail($contact);
        }

        // phones
        $phones = $request->get('phones');
        if (!empty($phones)) {
            foreach ($phones as $phoneData) {
                $this->addPhone($contact, $phoneData);
            }
            $this->getContactManager()->setMainPhone($contact);
        }

        // addresses
        $addresses = $request->get('addresses');
        if (!empty($addresses)) {
            foreach ($addresses as $addressData) {
                $address = $this->createAddress($addressData, $isMain);
                $contactManager->addAddress($contact, $address, $isMain);
            }
        }
        // set main address (if it was not set yet)
        $contactManager->setMainForCollection($contactManager->getAddressRelations($contact));

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

        // process details
        if ($request->get('bankAccounts') !== null) {
            $this->processBankAccounts($contact, $request->get('bankAccounts', array()));
        }
    }

    /**
     * Process all emails from request.
     *
     * @param $contact The contact on which is worked
     * @param $emails
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processEmails($contact, $emails)
    {
        $get = function ($email) {
            /** @var EmailEntity $email */

            return $email->getId();
        };

        $delete = function ($email) use ($contact) {
            return $contact->removeEmail($email);
        };

        $update = function ($email, $matchedEntry) {
            return $this->updateEmail($email, $matchedEntry);
        };

        $add = function ($email) use ($contact) {
            return $this->addEmail($contact, $email);
        };

        $result = $this->getRestHelper()->processSubEntities(
            $contact->getEmails(),
            $emails,
            $get,
            $add,
            $update,
            $delete
        );
        // check main
        $this->getContactManager()->setMainEmail($contact);

        return $result;
    }

    /**
     * Adds a new email to the given contact and persist it with the given object manager.
     *
     * @param $contact
     * @param $emailData
     *
     * @return bool
     *
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
            $email = new EmailEntity();
            $email->setEmail($emailData['email']);
            $email->setEmailType($emailType);
            $em->persist($email);
            $contact->addEmail($email);
        }

        return $success;
    }

    /**
     * Updates the given email address.
     *
     * @param EmailEntity $email The email object to update
     * @param array $entry The entry with the new data
     *
     * @return bool True if successful, otherwise false
     *
     * @throws EntityNotFoundException
     */
    protected function updateEmail(EmailEntity $email, $entry)
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
     * Process all urls of request.
     *
     * @param $contact The contact on which is processed
     * @param $urls
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUrls($contact, $urls)
    {
        $get = function ($url) {
            return $url->getId();
        };

        $delete = function ($url) use ($contact) {
            return $contact->removeUrl($url);
        };

        $update = function ($url, $matchedEntry) {
            return $this->updateUrl($url, $matchedEntry);
        };

        $add = function ($url) use ($contact) {
            return $this->addUrl($contact, $url);
        };

        $result = $this->getRestHelper()->processSubEntities($contact->getUrls(), $urls, $get, $add, $update, $delete);

        // check main
        $this->getContactManager()->setMainUrl($contact);

        return $result;
    }

    /**
     * Process all categories of request.
     *
     * @param $contact - the contact which is processed
     * @param $categories
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processCategories($contact, $categories)
    {
        $get = function ($category) {
            return $category->getId();
        };

        $delete = function ($category) use ($contact) {
            return $contact->removeCategorie($category);
        };

        $add = function ($category) use ($contact) {
            return $this->addCategories($contact, $category);
        };

        $result = $this->getRestHelper()->processSubEntities(
            $contact->getCategories(),
            $categories,
            $get,
            $add,
            null,
            $delete
        );

        return $result;
    }

    /**
     * Adds a new category to the given contact.
     *
     * @param $contact
     * @param $data
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addCategories($contact, $data)
    {
        $success = true;
        $categoryEntity = 'SuluCategoryBundle:Category';

        $category = $this->getDoctrine()
            ->getRepository($categoryEntity)
            ->find($data['id']);

        if (!$category) {
            throw new EntityNotFoundException($categoryEntity, $data['id']);
        } else {
            $contact->addCategorie($category);
        }

        return $success;
    }

    /**
     * @param UrlEntity $url
     * @param $entry
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    protected function updateUrl(UrlEntity $url, $entry)
    {
        $success = true;
        $urlTypeEntity = 'SuluContactBundle:UrlType';

        /** @var UrlTypeEntity $urlType */
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
     * Adds a new tag to the given contact.
     *
     * @param $contact
     * @param $data
     *
     * @return bool
     *
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
            $url = new UrlEntity();
            $url->setUrl($data['url']);
            $url->setUrlType($urlType);
            $em->persist($url);
            $contact->addUrl($url);
        }

        return $success;
    }

    /**
     * Process all phones from request.
     *
     * @param $contact The contact on which is processed
     * @param $phones
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processPhones($contact, $phones)
    {
        $get = function ($phone) {
            return $phone->getId();
        };

        $delete = function ($phone) use ($contact) {
            return $contact->removePhone($phone);
        };

        $update = function ($phone, $matchedEntry) {
            return $this->updatePhone($phone, $matchedEntry);
        };

        $add = function ($phone) use ($contact) {
            return $this->addPhone($contact, $phone);
        };

        $result = $this->getRestHelper()->processSubEntities(
            $contact->getPhones(),
            $phones,
            $get,
            $add,
            $update,
            $delete
        );

        // check main
        $this->getContactManager()->setMainPhone($contact);

        return $result;
    }

    /**
     * Add a new phone to the given contact and persist it with the given object manager.
     *
     * @param $contact
     * @param $phoneData
     *
     * @return bool True if there was no error, otherwise false
     *
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
            $phone = new PhoneEntity();
            $phone->setPhone($phoneData['phone']);
            $phone->setPhoneType($phoneType);
            $em->persist($phone);
            $contact->addPhone($phone);
        }

        return $success;
    }

    /**
     * Updates the given phone.
     *
     * @param PhoneEntity $phone The phone object to update
     * @param $entry The entry with the new data
     *
     * @return bool True if successful, otherwise false
     *
     * @throws EntityNotFoundException
     */
    protected function updatePhone(PhoneEntity $phone, $entry)
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
     *
     * @return bool
     */
    protected function processFaxes($contact, $faxes)
    {
        $get = function ($fax) {
            return $fax->getId();
        };

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

        $result = $this->getRestHelper()->processSubEntities(
            $contact->getFaxes(),
            $faxes,
            $get,
            $add,
            $update,
            $delete
        );
        // check main
        $this->getContactManager()->setMainFax($contact);

        return $result;
    }

    /**
     * @param $contact
     * @param $faxData
     *
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
            $fax = new FaxEntity();
            $fax->setFax($faxData['fax']);
            $fax->setFaxType($faxType);
            $em->persist($fax);
            $contact->addFax($fax);
        }
    }

    /**
     * @param FaxEntity $fax
     * @param $entry
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    protected function updateFax(FaxEntity $fax, $entry)
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
     * Creates an address based on the data passed.
     *
     * @param $addressData
     * @param $isMain returns if address is main address
     *
     * @return AddressEntity
     *
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
            $address = new AddressEntity();
            $address->setStreet($addressData['street']);
            $address->setNumber($addressData['number']);
            $address->setZip($addressData['zip']);
            $address->setCity($addressData['city']);
            $address->setState($addressData['state']);

            if (isset($addressData['note'])) {
                $address->setNote($addressData['note']);
            }
            if (isset($addressData['primaryAddress'])) {
                $isMain = $this->getBooleanValue($addressData['primaryAddress']);
            } else {
                $isMain = false;
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
     * Updates the given address.
     *
     * @param AddressEntity $address The phone object to update
     * @param mixed $entry The entry with the new data
     * @param Bool $isMain returns if address should be set to main
     *
     * @return bool True if successful, otherwise false
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function updateAddress(AddressEntity $address, $entry, &$isMain = null)
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

                if (isset($entry['note'])) {
                    $address->setNote($entry['note']);
                }

                if (isset($entry['primaryAddress'])) {
                    $isMain = $this->getBooleanValue($entry['primaryAddress']);
                } else {
                    $isMain = false;
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
     * Checks if a value is a boolean and converts it if necessary and returns it.
     *
     * @param $value
     *
     * @return bool
     */
    protected function getBooleanValue($value)
    {
        if (is_string($value)) {
            return $value === 'true' ? true : false;
        } elseif (is_bool($value)) {
            return $value;
        } elseif (is_numeric($value)) {
            return $value === 1 ? true : false;
        }
    }

    /**
     * Process all notes from request.
     *
     * @param ContactEntity $contact The contact on which is worked
     * @param $notes
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processNotes($contact, $notes)
    {
        $get = function ($note) {
            return $note->getId();
        };

        $delete = function ($note) use ($contact) {
            $contact->removeNote($note);

            return true;
        };

        $update = function ($note, $matchedEntry) {
            return $this->updateNote($note, $matchedEntry);
        };

        $add = function ($note) use ($contact) {
            return $this->addNote($contact, $note);
        };

        return $this->getRestHelper()->processSubEntities($contact->getNotes(), $notes, $get, $add, $update, $delete);
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager.
     *
     * @param $contact
     * @param $noteData
     *
     * @return bool True if there was no error, otherwise false
     *
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    protected function addNote($contact, $noteData)
    {
        $em = $this->getDoctrine()->getManager();
        $noteEntity = 'SuluContactBundle:Note';

        if (isset($noteData['id'])) {
            throw new EntityIdAlreadySetException($noteEntity, $noteData['id']);
        } else {
            $note = new NoteEntity();
            $note->setValue($noteData['value']);

            $em->persist($note);
            $contact->addNote($note);
        }

        return true;
    }

    /**
     * Updates the given note.
     *
     * @param NoteEntity $note
     * @param array $entry The entry with the new data
     *
     * @return bool True if successful, otherwise false
     */
    protected function updateNote(NoteEntity $note, $entry)
    {
        $success = true;

        $note->setValue($entry['value']);

        return $success;
    }

    /**
     * Process all tags of request.
     *
     * @param $contact The contact on which is worked
     * @param $tags
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processTags($contact, $tags)
    {
        $get = function ($tag) {
            return $tag->getId();
        };

        $delete = function ($tag) use ($contact) {
            return $contact->removeTag($tag);
        };

        $update = function () {
            return true;
        };

        $add = function ($tag) use ($contact) {
            return $this->addTag($contact, $tag);
        };

        return $this->getRestHelper()->processSubEntities($contact->getTags(), $tags, $get, $add, $update, $delete);
    }

    /**
     * Adds a new tag to the given contact and persist it with the given object manager.
     *
     * @param $contact
     * @param $data
     *
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
     * Process all bankAccounts of a request.
     *
     * @param $contact
     * @param $bankAccounts
     *
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processBankAccounts($contact, $bankAccounts)
    {
        $get = function ($bankAccount) {
            return $bankAccount->getId();
        };

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

        return $this->getRestHelper()->processSubEntities(
            $contact->getBankAccounts(),
            $bankAccounts,
            $get,
            $add,
            $update,
            $delete
        );
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager.
     *
     * @param $contact
     * @param $data
     *
     * @return bool
     *
     * @throws EntityIdAlreadySetException
     */
    protected function addBankAccount($contact, $data)
    {
        $em = $this->getDoctrine()->getManager();
        $entityName = 'SuluContactBundle:BankAccount';

        if (isset($data['id'])) {
            throw new EntityIdAlreadySetException($entityName, $data['id']);
        } else {
            $entity = new BankAccountEntity();
            $entity->setBankName($data['bankName']);
            $entity->setBic($data['bic']);
            $entity->setIban($data['iban']);
            $entity->setPublic($this->getBooleanValue((array_key_exists('public', $data) ? $data['public'] : false)));

            $em->persist($entity);
            $contact->addBankAccount($entity);
        }

        return true;
    }

    /**
     * Updates the given note.
     *
     * @param BankAccountEntity $entity The phone object to update
     * @param string $data The entry with the new data
     *
     * @return bool True if successful, otherwise false
     */
    protected function updateBankAccount(BankAccountEntity $entity, $data)
    {
        $success = true;

        $entity->setBankName($data['bankName']);
        $entity->setBic($data['bic']);
        $entity->setIban($data['iban']);
        $entity->setPublic($this->getBooleanValue((array_key_exists('public', $data) ? $data['public'] : false)));

        return $success;
    }

    /**
     * Process all addresses from request.
     *
     * @param $contact The contact on which is worked
     * @param $addresses
     *
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processAddresses($contact, $addresses)
    {
        $getAddressId = function ($addressRelation) {
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

        $result = $this->getRestHelper()->processSubEntities(
            $this->getContactManager()->getAddressRelations($contact),
            $addresses,
            $getAddressId,
            $add,
            $update,
            $delete
        );

        // check if main exists, else take first address
        $this->checkAndSetMainAddress($this->getContactManager()->getAddressRelations($contact));

        return $result;
    }

    /**
     * Get a position object.
     *
     * @param $id The position id
     *
     * @return mixed
     */
    protected function getPosition($id)
    {
        if ($id) {
            return $this->getDoctrine()->getRepository(self::$positionEntityName)->find($id);
        }

        return;
    }
}
