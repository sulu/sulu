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
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(
        0 => 'id',
        1 => 'title',
        2 => 'firstName',
        3 => 'lastName',
        4 => 'email',
        5 => 'account',
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array(
        'disabled' => 'public.deactivate',
        'email' => 'public.email',
        'account' => 'contact.contacts.company',
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
                        case 'account':
                            $newFields[] = 'accountContacts_account_name';
                            $joinConditions['accountContacts'] = 'accountContacts.main = TRUE';
                            break;
                        default:
                            $newFields[] = $field;
                    }
                }
                $request->query->add(array('fields' => implode($newFields, ',')));
            }

            $filter = function($res) {
                if (array_key_exists('emails_email', $res)) {
                    $res['email'] = $res['emails_email'];
                    unset($res['emails_email']);
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
                // create new account-contact relation
                $this->createMainAccountContact($contact, $parent);
            }

            $contact->setCreated(new DateTime());
            $contact->setChanged(new DateTime());

            $urls = $request->get('urls');
            if (!empty($urls)) {
                foreach ($urls as $urlData) {
                    $this->addUrl($contact, $urlData);
                }
            }

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
            $this->checkAndSetMainEmail($contact->getEmails());

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
                $contact->setPosition($request->get('position'));
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

}
