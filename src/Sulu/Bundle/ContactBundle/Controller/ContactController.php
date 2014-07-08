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
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Fax;
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
class ContactController extends AbstractContactController
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
    protected $fieldsExcluded = array('gender', 'newsletter');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('middleName', 'created', 'changed', 'birthday', 'salutation', 'formOfAddress', 'id', 'title', 'disabled');

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array(
//        'email',
        'account',
        'accountContacts_position',
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(
        0 => 'id',
        1 => 'title',
        2 => 'firstName',
        3 => 'lastName',
        5 => 'account',
//        4 => 'email',
//        6 => 'phone',
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array(
        'disabled' => 'public.deactivate',
        'email' => 'public.email',
        'phone' => 'public.phone',
        'account' => 'contact.contacts.company',
        'accountContacts_position' => 'contact.contacts.position',
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
        $where = array();
        $joinConditions = array();

        // flat structure
        if ($request->get('flat') == 'true') {

            /** @var ListRestHelper $listHelper */
            $listHelper = $this->get('sulu_core.list_rest_helper');

            // if fields are set
            if ($fields = $listHelper->getFields()) {
                $newFields = array();
                $where = array();

                foreach ($fields as $field) {
                    switch ($field) {
                        case 'email':
                            $newFields[] = 'emails_email';
                            $joinConditions['emails'] = 'emails.main = TRUE';
                            break;
                        case 'phone':
                            $newFields[] = 'phones_phone';
                            $joinConditions['phones'] = 'phones.main = TRUE';
                            break;
                        case 'account':
                            $newFields[] = 'accountContacts_account_name';
                            $joinConditions['accountContacts'] = 'accountContacts.main = TRUE';
                            break;
                        default:
                            $newFields[] = $field;
                    }
                }
                $request->query->add(array('fields' => implode(',', $newFields)));
            }

            // check if fullname should be returned
            $returnFullName = !is_null($fields) && array_search('fullName', $fields) !== false;

            $filter = function($res) use ($returnFullName){
                // get full name
                if ($returnFullName) {
                    $fullName = array();
                    if (array_key_exists('firstName', $res)) {
                        $fullName[] = $res['firstName'];
                    }
                    if (array_key_exists('middleName', $res)) {
                        $fullName[] = $res['middleName'];
                    }
                    if (array_key_exists('lastName', $res)) {
                        $fullName[] = $res['lastName'];
                    }
                    $res['fullName'] = implode(' ', $fullName);
                    $res['name'] = implode(' ', $fullName); // FIXME: name is only returned due to an error in auto-complete component
                }

                // filter relations
                if (array_key_exists('emails_email', $res)) {
                    $res['email'] = $res['emails_email'];
                    unset($res['emails_email']);
                }
                if (array_key_exists('phones_phone', $res)) {
                    $res['phone'] = $res['phones_phone'];
                    unset($res['phones_phone']);
                }
                if (array_key_exists('accountContacts_account_name', $res)) {
                    $res['account'] = $res['accountContacts_account_name'];
                    unset($res['accountContacts_account_name']);
                }
                return $res;
            };

            $view = $this->responseList($where, $this->entityName, $filter, $joinConditions);

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

            $urls = $contact->getUrls()->toArray();
            /** @var Url $url */
            foreach ($urls as $url) {
                if ($url->getAccounts()->count() == 0 && $url->getContacts()->count() == 1) {
                    $em->remove($url);
                }
            }

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

            $parentData = $request->get('account');
            if ($parentData != null && $parentData['id'] != null && $parentData['id'] != 'null' && $parentData['id'] != '') {
                /** @var Account $parent */
                $parent = $this->getDoctrine()
                    ->getRepository('SuluContactBundle:Account')
                    ->findAccountById($parentData['id']);

                if (!$parent) {
                    throw new EntityNotFoundException('SuluContactBundle:Account', $parentData['id']);
                }
                // create new account-contact relation
                $this->createMainAccountContact($contact, $parent);
            }
            // Since the position is related to the contact we have to set this
            // property after creating the new account-contact relation.
            $contact->setPosition($request->get('position'));

            $birthday = $request->get('birthday');
            if (!empty($birthday)) {
                $contact->setBirthday(new DateTime($birthday));
            }

            $contact->setCreated(new DateTime());
            $contact->setChanged(new DateTime());

            $contact->setFormOfAddress($formOfAddress[ 'id']);

            $contact->setDisabled($disabled);

            $salutation = $request->get('salutation');
            if (!empty($salutation)) {
                $contact->setSalutation($salutation);
            }

            // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
            $this->addNewContactRelations($contact, $request);

            // set new primary address
            if($this->newPrimaryAddress){
                $this->setNewPrimaryAddress($contact, $this->newPrimaryAddress);
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
     * returns the main account-contact relation or creates a new one
     * @param Contact $contact
     * @param Account $account
     * @return bool
     */
    private function getMainAccountContactOrCreateNew(Contact $contact, Account $account)
    {
        if (!$accountContact = $this->getMainAccountContact($contact)) {
            $accountContact = $this->createMainAccountContact($contact, $account);
        }
        return $accountContact;
    }

    /**
     * returns the main account-contact relation
     * @param Contact $contact
     * @return AccountContact|bool
     */
    private function getMainAccountContact(Contact $contact)
    {
        foreach ($contact->getAccountContacts() as $accountContact) {
            if ($accountContact->getMain()) {
                return $accountContact;
            }
        }
        return false;
    }

    /**
     * creates a new main Account Contacts relation
     * @param Contact $contact
     * @param Account $account
     * @return AccountContact
     */
    private function createMainAccountContact(Contact $contact, Account $account)
    {
        $accountContact = new AccountContact();
        $accountContact->setAccount($account);
        $accountContact->setContact($contact);
        $accountContact->setMain(true);
        $this->getDoctrine()->getManager()->persist($accountContact);
        $contact->addAccountContact($accountContact);
        return $accountContact;
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
                $contact->setChanged(new DateTime());

                // set account relation
                $parentData = $request->get('account');
                if ($parentData != null && $parentData['id'] != null && $parentData['id'] != 'null' && $parentData['id'] != '') {
                    /** @var Account $parent */
                    $parent = $this->getDoctrine()
                        ->getRepository('SuluContactBundle:Account')
                        ->findAccountById($parentData['id']);

                    if (!$parent) {
                        throw new EntityNotFoundException('SuluContactBundle:Account', $parentData['id']);
                    }
                    $accountContact = $this->getMainAccountContactOrCreateNew($contact, $parent);
                    if ($accountContact) {
                        $accountContact->setAccount($parent);
                    }
                } else {
                    if ($accountContact = $this->getMainAccountContact($contact)) {
                        $em->remove($accountContact);
                    }
                }
                // Since the position is related to the contact we have to set this
                // property after setting the account-contact relation.
                $contact->setPosition($request->get('position'));

                // process details
                if (!($this->processEmails($contact, $request->get('emails'))
                    && $this->processPhones($contact, $request->get('phones'))
                    && $this->processAddresses($contact, $request->get('addresses'))
                    && $this->processNotes($contact, $request->get('notes'))
                    && $this->processFaxes($contact, $request->get('faxes'))
                    && $this->processTags($contact, $request->get('tags'))
                    && $this->processUrls($contact, $request->get('urls')))
                ) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                $formOfAddress = $request->get('formOfAddress');
                if (!is_null($formOfAddress) && array_key_exists('id', $formOfAddress)) {
                    $contact->setFormOfAddress($formOfAddress['id']);
                }

                $disabled = $request->get('disabled');
                if (!is_null($disabled)) {
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

                // set new primary address
                if($this->newPrimaryAddress){
                    $this->setNewPrimaryAddress($contact, $this->newPrimaryAddress);
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
}
