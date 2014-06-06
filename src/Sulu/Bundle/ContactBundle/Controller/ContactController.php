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
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes contacts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class ContactController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluContactBundle:Contact';

    /**
     * @var string
     */
    protected $basePath = 'admin/api/contacts';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsDefault = array('lastName');

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('middleName', 'created', 'changed', 'birthday','salutation','formOfAddress','id', 'title','disabled');

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(0 => 'id', 1 => 'title');

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array(
        'disabled' => 'public.deactivate'
    );

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.contacts.';

    /**
     * returns all fields that can be used by list
     * @Get("contacts/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
    }

    /**
     * lists all contacts
     * optional parameter 'flat' calls listAction
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ($request->get('flat') == 'true') {
            // flat structure
            $view = $this->responseList();
        } else {
            $contacts = $this->getDoctrine()->getRepository($this->entityName)->findAll();
            $view = $this->view($this->createHalResponse($contacts), 200);
        }
        return $this->handleView($view);
    }

    /**
     * Deletes a Contact with the given ID from database
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            /** @var Contact $contact */
            $entityName = 'SuluContactBundle:Contact';
            $contact = $this->getDoctrine()
                ->getRepository($entityName)
                ->findByIdAndDelete($id);

            if (!$contact) {
                throw new EntityNotFoundException($entityName, $id);
            }

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

//            $urls = $contact->getUrls()->toArray();
//            /** @var Url $url */
//            foreach ($urls as $url) {
//                if ($url->getAccounts()->count() == 0 && $url->getContacts()->count() == 1) {
//                    $em->remove($url);
//                }
//            }

            $faxes = $contact->getFaxes()->toArray();
            /** @var Fax $fax */
            foreach ($faxes as $fax) {
                if ($fax->getAccounts()->count() == 0 && $fax->getContacts()->count() == 1) {
                    $em->remove($fax);
                }
            }

            $em->remove($contact);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Shows the contact with the given Id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function ($id) {
                return $this->getDoctrine()
                    ->getRepository('SuluContactBundle:Contact')
                    ->findById($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * Creates a new contact
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $firstName = $request->get('firstName');
        $lastName = $request->get('lastName');
        $disabled = $request->get('disabled');
        $formOfAddress = $request->get('formOfAddress');

        try {
            if ($firstName == null) {
                throw new RestException('There is no first name for the contact');
            }
            if ($lastName == null) {
                throw new RestException('There is no last name for the contact');
            }
            if (is_null($disabled)) {
                throw new RestException('There is no disabled flag for the contact');
            }
            if (is_null($formOfAddress) || !array_key_exists('id', $formOfAddress)) {
                throw new RestException('There is no form of address for the contact');
            }

            $em = $this->getDoctrine()->getManager();

            // Standard contact fields
            $contact = new Contact();
            $contact->setFirstName($firstName);
            $contact->setLastName($lastName);

            $contact->setTitle($request->get('title'));
            $contact->setPosition($request->get('position'));

            $parentData = $request->get('account');
            if ($parentData != null && $parentData['id'] != null && $parentData['id'] != 'null' && $parentData['id'] != '') {
                /** @var Account $parent */
                $parent = $this->getDoctrine()
                    ->getRepository('SuluContactBundle:Account')
                    ->findAccountById($parentData['id']);

                if (!$parent) {
                    throw new EntityNotFoundException('SuluContactBundle:Account', $parentData['id']);
                }
                $contact->setAccount($parent);
            }

            $contact->setCreated(new DateTime());
            $contact->setChanged(new DateTime());

//            $urls = $request->get('urls');
//            if (!empty($urls)) {
//                foreach ($urls as $urlData) {
//                    $this->addUrl($contact, $urlData);
//                }
//            }

            $faxes = $request->get('faxes');
            if (!empty($faxes)) {
                foreach ($faxes as $faxData) {
                    $this->addFax($contact, $faxData);
                }
            }

            $emails = $request->get('emails');
            if (!empty($emails)) {
                foreach ($emails as $emailData) {
                    $this->addEmail($contact, $emailData);
                }
            }

            $phones = $request->get('phones');
            if (!empty($phones)) {
                foreach ($phones as $phoneData) {
                    $this->addPhone($contact, $phoneData);
                }
            }

            $addresses = $request->get('addresses');
            if (!empty($addresses)) {
                foreach ($addresses as $addressData) {
                    $this->addAddress($contact, $addressData);
                }
            }

            $notes = $request->get('notes');
            if (!empty($notes)) {
                foreach ($notes as $noteData) {
                    $this->addNote($contact, $noteData);
                }
            }

            $birthday = $request->get('birthday');
            if (!empty($birthday)) {
               $contact->setBirthday(new DateTime($birthday));
            }

            $contact->setFormOfAddress($formOfAddress['id']);

            $contact->setDisabled($disabled);

            $salutation = $request->get('salutation');
            if (!empty($salutation)) {
                $contact->setSalutation($salutation);
            }

            // handle tags
            $tags = $request->get('tags');
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $this->addTag($contact, $tag);
                }
            }

            $em->persist($contact);

            $em->flush();

            $view = $this->view($contact, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id, Request $request)
    {
        $contactEntity = 'SuluContactBundle:Contact';

        try {
            /** @var Contact $contact */
            $contact = $this->getDoctrine()
                ->getRepository($contactEntity)
                ->findById($id);

            if (!$contact) {
                throw new EntityNotFoundException($contactEntity, $id);
            } else {

                $em = $this->getDoctrine()->getManager();

                // Standard contact fields
                $contact->setFirstName($request->get('firstName'));
                $contact->setLastName($request->get('lastName'));

                $contact->setTitle($request->get('title'));
                $contact->setPosition($request->get('position'));

                $parentData = $request->get('account');
                if ($parentData != null && $parentData['id'] != null && $parentData['id'] != 'null' && $parentData['id'] != '') {
                    /** @var Account $parent */
                    $parent = $this->getDoctrine()
                        ->getRepository('SuluContactBundle:Account')
                        ->findAccountById($parentData['id']);

                    if (!$parent) {
                        throw new EntityNotFoundException('SuluContactBundle:Account', $parentData['id']);
                    }
                    $contact->setAccount($parent);
                } else {
                    $contact->setAccount(null);
                }

                $contact->setChanged(new DateTime());

                // process details
                if (!($this->processEmails($contact, $request)
                    && $this->processPhones($contact, $request)
                    && $this->processAddresses($contact, $request)
                    && $this->processNotes($contact, $request)
                    && $this->processFaxes($contact, $request)
                    && $this->processTags($contact, $request)
                    && $this->processUrls($contact, $request))
                ) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                $formOfAddress = $request->get('formOfAddress');
                if(!is_null($formOfAddress) && array_key_exists('id', $formOfAddress)){
                    $contact->setFormOfAddress($formOfAddress['id']);
                }

                $disabled = $request->get('disabled');
                if(!is_null($disabled)){
                    $contact->setDisabled($disabled);
                }

                $salutation = $request->get('salutation');
                if (!empty($salutation)) {
                    $contact->setSalutation($salutation);
                }

                $birthday = $request->get('birthday');
                if (!empty($birthday)) {
                    $contact->setBirthday(new DateTime($birthday));
                }

                $em->flush();

                $view = $this->view($contact, 200);
            }
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Process all emails from request
     * @param Contact $contact The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processEmails(Contact $contact, Request $request)
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

        return $this->processPut($contact->getEmails(), $emails, $delete, $update, $add);
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
     * Process all tags of request
     * @param Contact $contact The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processTags(Contact $contact, Request $request)
    {
        $tags = $request->get('tags');

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
     * @param Contact $contact
     * @param $data
     * @return bool True if there was no error, otherwise false
     */
    protected function addTag(Contact $contact, $data)
    {
        $success = true;
        $tagManager = $this->get('sulu_tag.tag_manager');
        $resolvedTag = $tagManager->findByName($data);
        $contact->addTag($resolvedTag);

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
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    private function addFax(Contact $contact, $faxData)
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
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
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

            if (isset($addressData['primaryAddress'])) {
                $address->setPrimaryAddress($this->getBooleanValue($addressData['primaryAddress']));
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

            if (isset($entry['primaryAddress'])) {

                $address->setPrimaryAddress($this->getBooleanValue($entry['primaryAddress']));
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
