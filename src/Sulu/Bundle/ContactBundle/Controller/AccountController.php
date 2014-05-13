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
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use \DateTime;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes accounts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class AccountController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluContactBundle:Account';
    protected $contactsEntityName = 'SuluContactBundle:Contact';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsDefault = array('name');

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('created', 'type', 'division', 'disabled', 'uid');

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(0 => 'id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array('id' => 'public.id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsEditable = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsValidation = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsWidth = array('type' => '150px');

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.account.';

    /**
     * returns all fields that can be used by list
     * @Get("accounts/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
    }

    /**
     * persists a setting
     * @Put("accounts/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single account with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $includes = explode(',', $request->get('include'));

        $view = $this->responseGetById(
            $id,
            function ($id) use ($includes) {
                return $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->findAccountById($id, in_array('contacts', $includes));
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all contacts of an account
     * optional parameter 'flat' calls listAction
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getContactsAction($id, Request $request)
    {
        if ($request->get('flat') == 'true') {
            // flat structure
            $view = $this->responseList(array('account_id'=> $id), $this->contactsEntityName);
        } else {
            $contacts = $this->getDoctrine()->getRepository($this->contactsEntityName)->findByAccountId($id);
            $view = $this->view($this->createHalResponse($contacts), 200);
        }
        return $this->handleView($view);
    }

    /**
     * lists all accounts
     * optional parameter 'flat' calls listAction
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $where = array();
        $type = $request->get('type');
        if ($type) {
            $where['type'] = $type;
        }
        if ($request->get('flat') == 'true') {
            $view = $this->responseList($where);
        } else {
            $contacts = $this->getDoctrine()->getRepository($this->entityName)->findAll();
            $view = $this->view($this->createHalResponse($contacts), 200);
        }
        return $this->handleView($view);
    }

    /**
     * Creates a new account
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        try {
            if ($name == null) {
                throw new RestException('There is no name for the account given');
            }

            $em = $this->getDoctrine()->getManager();

            $account = new Account();

            $account->setName($request->get('name'));

            $account->setType($request->get('type'));


            $disabled = $request->get('disabled');
            if (is_null($disabled)) {
                $disabled = false;
            }
            $account->setDisabled($disabled);

            $parentData = $request->get('parent');
            if ($parentData != null && isset($parentData['id']) && $parentData['id'] != 'null' && $parentData['id'] != '') {
                $parent = $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->findAccountById($parentData['id']);

                if (!$parent) {
                    throw new EntityNotFoundException($this->entityName, $parentData['id']);
                }
                $account->setParent($parent);
            }

            // set creator / changer
            $account->setCreated(new DateTime());
            $account->setChanged(new DateTime());
            $account->setCreator($this->getUser());
            $account->setChanger($this->getUser());

            $urls = $request->get('urls');
            if (!empty($urls)) {
                foreach ($urls as $urlData) {
                    $this->addUrl($account, $urlData);
                }
            }

            $emails = $request->get('emails');
            if (!empty($emails)) {
                foreach ($emails as $emailData) {
                    $this->addEmail($account, $emailData);
                }
            }

            $phones = $request->get('phones');
            if (!empty($phones)) {
                foreach ($phones as $phoneData) {
                    $this->addPhone($account, $phoneData);
                }
            }

            $faxes = $request->get('faxes');
            if (!empty($faxes)) {
                foreach ($faxes as $faxData) {
                    $this->addFax($account, $faxData);
                }
            }

            $addresses = $request->get('addresses');
            if (!empty($addresses)) {
                foreach ($addresses as $addressData) {
                    $this->addAddress($account, $addressData);
                }
            }

            $notes = $request->get('notes');
            if (!empty($notes)) {
                foreach ($notes as $noteData) {
                    $this->addNote($account, $noteData);
                }
            }

            $em->persist($account);

            $em->flush();

            $view = $this->view($account, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing contact with the given id
     * @param integer $id The id of the contact to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        $accountEntity = 'SuluContactBundle:Account';

        try {
            /** @var Account $account */
            $account = $this->getDoctrine()
                ->getRepository($accountEntity)
                ->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($accountEntity, $id);
            } else {

                $em = $this->getDoctrine()->getManager();

                // set name
                $account->setName($request->get('name'));

                // set disabled
                $disabled = $request->get('disabled');
                if (!is_null($disabled)) {
                    $account->setDisabled($disabled);
                }

                // set parent
                $parentData = $request->get('parent');
                if ($parentData != null && isset($parentData['id']) && $parentData['id'] != 'null' && $parentData['id'] != '') {
                    $parent = $this->getDoctrine()
                        ->getRepository($this->entityName)
                        ->findAccountById($parentData['id']);
                    if (!$parent) {
                        throw new EntityNotFoundException($this->entityName, $parentData['id']);
                    }
                    $account->setParent($parent);
                } else {
                    $account->setParent(null);
                }

                // set changed
                $account->setChanged(new DateTime());
                $user = $this->getUser();
                $account->setChanger($user);

                // process details
                if (!($this->processUrls($account, $request)
                    && $this->processEmails($account, $request)
                    && $this->processFaxes($account, $request)
                    && $this->processPhones($account, $request)
                    && $this->processAddresses($account, $request)
                    && $this->processNotes($account, $request))
                ) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                $em->flush();

                $view = $this->view($account, 200);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Delete an account with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, Request $request)
    {
        $delete = function ($id) use ($request) {
            $entityName = 'SuluContactBundle:Account';

            /* @var Account $account */
            $account = $this->getDoctrine()
                ->getRepository($entityName)
                ->findAccountByIdAndDelete($id);

            if (!$account) {
                throw new EntityNotFoundException($entityName, $id);
            }

            // do not allow to delete entity if child is existent
            if (!$account->getChildren()->count()) {
                // return 405 error
            }

            $em = $this->getDoctrine()->getManager();

            // remove related contacts if removeContacts is true
            if (!is_null($request->get('removeContacts')) &&
                $request->get('removeContacts') == "true"
            ) {
                foreach ($account->getContacts() as $contact) {
                    $em->remove($contact);
                }
            }

            $em->remove($account);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all urls from request
     * @param Account $account The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processUrls(Account $account, Request $request)
    {
        $urls = $request->get('urls');

        $delete = function ($url) use ($account) {
            $account->removeUrl($url);

            return true;
        };

        $update = function ($url, $matchedEntry) {
            return $this->updateUrl($url, $matchedEntry);
        };

        $add = function ($url) use ($account) {
            $this->addUrl($account, $url);

            return true;
        };

        return $this->processPut($account->getUrls(), $urls, $delete, $update, $add);
    }

    /**
     * Adds URL to an account
     * @param Account $account
     * @param $urlData
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addUrl(Account $account, $urlData)
    {
        $em = $this->getDoctrine()->getManager();
        $urlEntity = 'SuluContactBundle:Url';
        $urlTypeEntity = 'SuluContactBundle:UrlType';

        $urlType = $this->getDoctrine()
            ->getRepository($urlTypeEntity)
            ->find($urlData['urlType']['id']);

        if (isset($urlData['id'])) {
            throw new EntityIdAlreadySetException($urlEntity, $urlData['id']);
        } elseif (!$urlType) {
            throw new EntityNotFoundException($urlTypeEntity, $urlData['urlType']['id']);
        } else {
            $url = new Url();
            $url->setUrl($urlData['url']);
            $url->setUrlType($urlType);
            $em->persist($url);
            $account->addUrl($url);
        }
    }

    /**
     * Updates the given url address
     * @param Url $url The email object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
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
     * Process all emails from request
     * @param Account $account The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processEmails(Account $account, Request $request)
    {
        $emails = $request->get('emails');

        $delete = function ($email) use ($account) {
            $account->removeEmail($email);

            return true;
        };

        $update = function ($email, $matchedEntry) {
            return $this->updateEmail($email, $matchedEntry);
        };

        $add = function ($email) use ($account) {
            $this->addEmail($account, $email);

            return true;
        };

        return $this->processPut($account->getEmails(), $emails, $delete, $update, $add);
    }

    /**
     * Process all faxes from request
     * @param Account $account The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processFaxes(Account $account, Request $request)
    {
        $faxes = $request->get('faxes');

        $delete = function ($fax) use ($account) {
            $account->removeFax($fax);

            return true;
        };

        $update = function ($fax, $matchedEntry) {
            return $this->updateFax($fax, $matchedEntry);
        };

        $add = function ($fax) use ($account) {
            $this->addFax($account, $fax);

            return true;
        };

        return $this->processPut($account->getFaxes(), $faxes, $delete, $update, $add);
    }

    /**
     * Adds an email address to an account
     * @param Account $account
     * @param $faxData
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addFax(Account $account, $faxData)
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
            $account->addFax($fax);
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
     * Adds an email address to an account
     * @param Account $account
     * @param $emailData
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addEmail(Account $account, $emailData)
    {
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
            $account->addEmail($email);
        }
    }

    /**
     * Updates the given email address
     * @param Email $email The email object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
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
     * Process all phones from request
     * @param Account $account The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processPhones(Account $account, Request $request)
    {
        $phones = $request->get('phones');

        $delete = function ($phone) use ($account) {
            $account->removePhone($phone);

            return true;
        };

        $update = function ($phone, $matchedEntry) {
            return $this->updatePhone($phone, $matchedEntry);
        };

        $add = function ($phone) use ($account) {
            return $this->addPhone($account, $phone);
        };

        return $this->processPut($account->getPhones(), $phones, $delete, $update, $add);
    }

    /**
     * Adds a phone number to an account
     * @param Account $account
     * @param $phoneData
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addPhone(Account $account, $phoneData)
    {
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
            $url = new Phone();
            $url->setPhone($phoneData['phone']);
            $url->setPhoneType($phoneType);
            $em->persist($url);
            $account->addPhone($url);
        }

        return true;
    }

    /**
     * Updates the given phone
     * @param Phone $phone The phone object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
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
     * Process all addresses from request
     * @param Account $account The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processAddresses(Account $account, Request $request)
    {
        $addresses = $request->get('addresses');

        $delete = function ($address) use ($account) {
            $account->removeAddresse($address);

            return true;
        };

        $update = function ($address, $matchedEntry) {
            return $this->updateAddress($address, $matchedEntry);
        };

        $add = function ($address) use ($account) {
            $this->addAddress($account, $address);

            return true;
        };

        return $this->processPut($account->getAddresses(), $addresses, $delete, $update, $add);
    }

    /**
     * Adds an address to an account
     * @param Account $account
     * @param $addressData
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addAddress(Account $account, $addressData)
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
            $address->setCountry($country);
            $address->setAddressType($addressType);

            // add additional fields
            if (isset($addressData['addition'])) {
                $address->setAddition($addressData['addition']);
            }

            $em->persist($address);

            $account->addAddresse($address);
        }
    }

    /**
     * Updates the given address
     * @param Address $address The phone object to update
     * @param mixed $entry The entry with the new data
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
     * @param Account $account The contact on which is worked
     * @param Request $request
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processNotes(Account $account, Request $request)
    {
        $notes = $request->get('notes');

        $delete = function ($note) use ($account) {
            $account->removeNote($note);

            return true;
        };

        $update = function ($note, $matchedEntry) {
            return $this->updateNote($note, $matchedEntry);
        };

        $add = function ($note) use ($account) {
            return $this->addNote($account, $note);
        };

        return $this->processPut($account->getNotes(), $notes, $delete, $update, $add);
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager
     * @param Account $account
     * @param $noteData
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    protected function addNote(Account $account, $noteData)
    {
        $em = $this->getDoctrine()->getManager();
        $noteEntity = 'SuluContactBundle:Note';

        if (isset($noteData['id'])) {
            throw new EntityIdAlreadySetException($noteEntity, $noteData['id']);
        } else {
            $note = new Note();
            $note->setValue($noteData['value']);

            $em->persist($note);
            $account->addNote($note);
        }

        return true;
    }

    /**
     * Updates the given note
     * @param Note $note The phone object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     */
    protected function updateNote(Note $note, $entry)
    {
        $success = true;

        $note->setValue($entry['value']);

        return $success;
    }

    /**
     * returns delete info for multiple ids
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function multipledeleteinfoAction(Request $request)
    {

        $ids = $request->get('ids');

        $response = array();
        $numContacts = 0;
        $numChildren = 0;

        foreach ($ids as $id) {
            /** @var Account $account */
            $account = $this->getDoctrine()
                ->getRepository('SuluContactBundle:Account')
                ->find($id);

            // get number of subaccounts
            $numChildren += $account->getChildren()->count();

            // get full number of contacts
            $numContacts += $account->getContacts()->count();
        }


        $response['numContacts'] = $numContacts;
        $response['numChildren'] = $numChildren;

        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Returns information about data which will be also deleted:
     * 3 contacts, total number of contacts, and if deleting is allowed (as 0 or 1)
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDeleteinfoAction($id)
    {
        $response = array();
        $response['contacts'] = array();

        /** @var Account $account */
        $account = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Account')
            ->find($id);


        if ($account != null) {

            // return a maximum of 3 accounts
            $slicedContacts = $account->getContacts()->slice(0, 3);

            foreach ($slicedContacts as $contact) {
                /** @var Contact $contact */
                $response['contacts'][] = array(
                    'id' => $contact->getId(),
                    'firstName' => $contact->getFirstName(),
                    'middleName' => $contact->getMiddleName(),
                    'lastName' => $contact->getLastName(),
                );
            }

            // return number of contact
            $response['numContacts'] = $account->getContacts()->count();

            // get number of sub companies
            $response['numChildren'] = $account->getChildren()->count();

            if ($response['numChildren'] > 0) {
                // if account has a subcompany do not allow to delete
                $slicedChildren = $account->getChildren()->slice(0, 3);

                /* @var Account $sc */
                foreach ($slicedChildren as $sc) {
                    $child = array();
                    $child['id'] = $sc->getId();
                    $child['name'] = $sc->getName();

                    $response['children'][] = $child;
                }
            }

            $view = $this->view($response, 200);

        } else {
            $view = $this->view(null, 404);
        }

        return $this->handleView($view);
    }

}
