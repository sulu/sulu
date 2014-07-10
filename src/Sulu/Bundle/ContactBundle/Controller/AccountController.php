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
use FOS\RestBundle\Controller\Annotations\Post;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery;
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use \DateTime;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Makes accounts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class AccountController extends AbstractContactController
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluContactBundle:Account';
    protected $contactEntityName = 'SuluContactBundle:Contact';
    protected $accountCategoryEntityName = 'SuluContactBundle:AccountCategory';
    protected $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected $termsOfPaymentEntityName = 'SuluContactBundle:TermsOfPayment';
    protected $termsOfDeliveryEntityName = 'SuluContactBundle:TermsOfDelivery';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array('lft', 'rgt', 'depth', 'city','mainContact');

    /**
     * {@inheritdoc}
     */
    protected $fieldsDefault = array('name');

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array('lft', 'rgt', 'depth','externalId');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array(
        'id',
        'created',
        'changed',
        'type',
        'disabled',
        'uid',
        'registerNumber',
        'placeOfJurisdiction',
        'mainUrl',
        'mainFax',
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array(
        'city'
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(
        0 => 'number',
        1 => 'name',
        2 => 'corporation',
        5 => 'city',
        5 => 'mainContact',
        6 => 'mainPhone',
        7 => 'mainEmail',
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array(
        'id' => 'public.id',
        'disabled' => 'public.deactivate',
        'mainEmail' => 'public.email',
        'mainPhone' => 'public.phone',
        'mainUrl' => 'public.url',
        'mainFax' => 'public.fax',
        'city' => 'contact.address.city',
        'mainContact' => 'contact.contacts.main-contact',
    );

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
    protected $fieldsWidth = array(
        'type' => '150px',
        'number' => '90px',
        'name' => '300px',
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsMinWidth = array(
        'name' => '150px',
    );

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.accounts.';

    /**
     * returns all fields that can be used by list
     * @return mixed
     */
    public function fieldsAction()
    {
        return $this->responseFields();
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

            $filterMainContact = null;
            $listHelper = $this->get('sulu_core.list_rest_helper');
            $fields = $listHelper->getFields();

            // check if contact is principle point of contact
            if ($fields && array_search('isMainContact',$fields)) {
                $mainContactString = 'accountContacts_account_mainContact_id';
                // add to fields to query
                $fields[] = $mainContactString;
                $request->query->add(array('fields' => implode(',', $fields)));
                // filter result
                $filterMainContact = function($content) use ($mainContactString, $fields) {
                    if (array_search('isMainContact',$fields)) {
                        $content['isMainContact'] = $content['id'] === $content[$mainContactString];
                    }
                    unset($content[$mainContactString]);
                    return $content;
                };
            }

            // flat structure
            $view = $this->responseList(array('accountContacts_account_id' => $id), $this->contactEntityName, $filterMainContact);
        } else {
            $contacts = $this->getDoctrine()->getRepository($this->contactEntityName)->findByAccountId($id);
            $view = $this->view($this->createHalResponse($contacts), 200);
        }
        return $this->handleView($view);
    }

    /**
     * @param $accountId
     * @param $contactId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function putContactsAction($accountId, $contactId, Request $request)
    {
        try {
            // get account
            /** @var Account $account */
            $account = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($accountId);
            if (!$account) {
                throw new EntityNotFoundException('account', $accountId);
            }

            // get contact
            $contact = $this->getDoctrine()
                ->getRepository($this->contactEntityName)
                ->find($contactId);
            if (!$contact) {
                throw new EntityNotFoundException('contact', $contactId);
            }

            // check if relation already exists
            $accountContact = $this->getDoctrine()
                ->getRepository($this->accountContactEntityName)
                ->findOneBy(array('contact' => $contact, 'account' => $account));
            if ($accountContact) {
                throw new \Exception('Relation already exists');
            }

            // create relation
            $accountContact = new AccountContact();
            // if contact has no main relation - set as main
            $accountContact->setMain($contact->getAccountContacts()->isEmpty());
            $accountContact->setAccount($account);
            $accountContact->setContact($contact);
            $accountContact->setPosition($request->get('position'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($accountContact);
            $em->flush();

            $view = $this->view($contact, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deleted account contact
     * @param $accountId
     * @param $contactId
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function deleteContactsAction($accountId, $contactId)
    {
        try {
            // check if relation exists
            $accountContact = $this->getDoctrine()
                ->getRepository($this->accountContactEntityName)
                ->findByForeignIds($accountId, $contactId);
            if (!$accountContact) {
                throw new EntityNotFoundException('AccountContact', $accountId . $contactId);
            }
            $id = $accountContact->getId();

            // remove accountContact
            $em = $this->getDoctrine()->getManager();
            $em->remove($accountContact);
            $em->flush();

            $view = $this->view($id, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
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

            /** @var ListRestHelper $listHelper */
            $listHelper = $this->get('sulu_core.list_rest_helper');

            // if fields are set
            if ($fields = $listHelper->getFields()) {
                $newFields = array();
                $where = array();


                $mappings = array(
                    'city' => 'accountAddresses_address_city',
                    'mainContact' => 'mainContact_lastName',
                );

                foreach ($fields as $field) {
                    switch ($field) {
                        case 'city':
                            $newFields[] = $mappings[$field];
                            $joinConditions['accountAddresses'] = 'accountAddresses.main = TRUE';
                            break;
                        case 'mainContact':
                            $newFields[] = $mappings[$field];
                            break;
                        default:
                            $newFields[] = $field;
                    }
                }
                $request->query->add(array('fields' => implode(',', $newFields)));
            }
            $filter = function ($res) use ($mappings) {
                // filter relations
                if (array_key_exists($mappings['city'], $res)) {
                    $res['city'] = $res[$mappings['city']];
                    unset($res[$mappings['city']]);
                }
                //
                if (array_key_exists($mappings['mainContact'], $res)) {
                    $res['mainContact'] = $res[$mappings['mainContact']];
                    unset($res[$mappings['mainContact']]);
                }
                return $res;
            };

            $view = $this->responseList($where, null, $filter, $joinConditions);
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

            $account->setCorporation($request->get('corporation'));

            $account->setType($request->get('type', 0));

            $disabled = $request->get('disabled');
            if (is_null($disabled)) {
                $disabled = false;
            }
            $account->setDisabled($disabled);

            // set category
            // FIXME: check if accountcategory with given value exists
            $this->setCategory($request->get('accountCategory'), $account);

            // set parent
            $this->setParent($request->get('parent'), $account);

            // set creator / changer
            $account->setCreated(new DateTime());
            $account->setChanged(new DateTime());
            $account->setCreator($this->getUser());
            $account->setChanger($this->getUser());

            // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
            $this->addNewContactRelations($account, $request, $this->getContactManager());

            // set new primary address
            if ($this->newPrimaryAddress) {
                $this->setNewPrimaryAddress($account, $this->newPrimaryAddress);
            }

            $this->processTerms($request, $account);

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
                $account->setCorporation($request->get('corporation'));

                // set disabled
                $disabled = $request->get('disabled');
                if (!is_null($disabled)) {
                    $account->setDisabled($disabled);
                }

                // set category
                // FIXME: check if accountcategory with given value exists
                $this->setCategory($request->get('accountCategory'), $account);

                // set parent
                $this->setParent($request->get('parent'), $account);

                // set changed
                $account->setChanged(new DateTime());
                $user = $this->getUser();
                $account->setChanger($user);

                // process details
                if (!($this->processUrls($account, $request->get('urls'))
                    && $this->processEmails($account, $request->get('emails'))
                    && $this->processFaxes($account, $request->get('faxes'))
                    && $this->processPhones($account, $request->get('phones'))
                    && $this->processAccountAddresses($account, $request->get('addresses'))
                    && $this->processTags($account, $request->get('tags'))
                    && $this->processNotes($account, $request->get('notes')))
                ) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                // set new primary address
                if ($this->newPrimaryAddress) {
                    $this->setNewPrimaryAddress($account, $this->newPrimaryAddress);
                }

                $this->processTerms($request, $account);

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
     * set parent to account
     * @param array $parentData
     * @param Account $account
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function setParent($parentData, Account $account)
    {
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
    }

    /**
     * set category to account
     * @param array $categoryData
     * @param Account $account
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function setCategory($categoryData, Account $account)
    {
        $categoryId = $categoryData['id'];
        if (!is_null($categoryId) && !empty($categoryId)) {
            /** @var @var AccountCategory $category */
            $category = $this->getDoctrine()->getRepository($this->accountCategoryEntityName)->find($categoryId);
            if (!is_null($category)) {
                $account->setAccountCategory($category);
            } else {
                throw new EntityNotFoundException($this->accountCategoryEntityName, $categoryId);
            }
        }
    }

    /**
     * partial update of account infos
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction($id, Request $request)
    {
        $accountEntity = 'SuluContactBundle:Account';

        $em = $this->getDoctrine()->getManager();

        try {
            /** @var Account $account */
            $account = $em->getRepository($accountEntity)
                ->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($accountEntity, $id);
            } else {

                if (!is_null($request->get('uid'))) {
                    $account->setUid($request->get('uid'));
                }
                if (!is_null($request->get('registerNumber'))) {
                    $account->setRegisterNumber($request->get('registerNumber'));
                }
                if (!is_null($request->get('number'))) {
                    $account->setNumber($request->get('number'));
                }

                if (!is_null($request->get('placeOfJurisdiction'))) {
                    $account->setPlaceOfJurisdiction($request->get('placeOfJurisdiction'));
                }

                // check if mainContact is set
                if (!is_null($mainContactRequest = $request->get('mainContact'))) {
                    $mainContact = $em->getRepository($this->contactEntityName)->find($mainContactRequest['id']);
                    if ($mainContact) {
                        $account->setMainContact($mainContact);
                    }
                }

                // process details
                if (!is_null($request->get('bankAccounts'))) {
                    $this->processBankAccounts($account, $request->get('bankAccounts'));
                }

                $this->processTerms($request, $account);

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
     * Processes terms of delivery and terms of payment for an account
     * @param Request $request
     * @param Account $account
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function processTerms(Request $request, Account $account)
    {
        if (!is_null($request->get('termsOfPayment'))) {
            $id = $request->get('termsOfPayment')['id'];
            /** @var TermsOfPayment $termsOfPayment */
            $termsOfPayment = $this->getDoctrine()
                ->getRepository($this->termsOfPaymentEntityName)
                ->find($id);

            if (!$termsOfPayment) {
                throw new EntityNotFoundException($this->termsOfPaymentEntityName, $id);
            }
            $account->setTermsOfPayment($termsOfPayment);
        }

        if (!is_null($request->get('termsOfDelivery'))) {
            $id = $request->get('termsOfDelivery')['id'];
            /** @var TermsOfDelivery $termsOfDelivery */
            $termsOfDelivery = $this->getDoctrine()
                ->getRepository($this->termsOfDeliveryEntityName)
                ->find($id);
            if (!$termsOfDelivery) {
                throw new EntityNotFoundException($this->termsOfDeliveryEntityName, $id);
            }
            $account->setTermsOfDelivery($termsOfDelivery);
        }
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
                foreach ($account->getAccountContacts() as $accountContact) {
                    $em->remove($accountContact->getContact());
                }
            }

            $em->remove($account);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
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
                ->countDistinctAccountChildrenAndContacts($id);

            // get number of subaccounts
            $numChildren += $account['numChildren'];

            // FIXME: distinct contacts: (currently the same contacts could be counted multiple times)
            // get full number of contacts
            $numContacts += $account['numContacts'];;
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
            ->findChildrenAndContacts($id);

        if ($account != null) {
            // return a maximum of 3 accounts
            $slicedContacts = array();
            $accountContacts = $account->getAccountContacts();
            $numContacts = 0;
            if (!is_null($accountContacts)) {
                foreach ($accountContacts as $accountContact) {
                    $contactId = $accountContact->getContact()->getId();
                    if (!array_key_exists($contactId, $slicedContacts)) {
                        if ($numContacts++ < 3) {
                            $slicedContacts[$contactId] = $accountContact->getContact();
                        }
                    }
                }
            }

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
            $response['numContacts'] = $numContacts;

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

    /**
     * Converts an account to a different account type
     * @Post("/accounts/{id}")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTriggerAction($id, Request $request)
    {

        $action = $request->get('action');
        $em = $this->getDoctrine()->getManager();
        $view = null;

        try {
            switch ($action) {
                case 'convertAccountType':
                    $accountType = $request->get('type');
                    $accountEntity = $this->getDoctrine()
                        ->getRepository('SuluContactBundle:Account')
                        ->find($id);

                    if (!$accountEntity) {
                        throw new EntityNotFoundException($accountEntity, $id);
                    }

                    if (!$accountType) {
                        throw new RestException("There is no type to convert to given!");
                    }

                    $this->convertToType($accountEntity, $accountType);
                    $em->flush();

                    $view = $this->view($accountEntity, 200);
                    break;
                default:
                    throw new RestException("Unrecognized action: " . $action);

            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Converts an account to another account type when allowed
     * @param $account
     * @param $type string representation
     * @throws RestException
     */
    protected function convertToType(Account $account, $type)
    {
        $config = $this->container->getParameter('sulu_contact.account_types');
        $types = $this->getAccountTypes($config);
        $transitionsForType = $this->getAccountTypeTransitions(
            $config,
            $types,
            array_search($account->getType(), $types)
        );

        if ($type && $this->isTransitionAllowed($transitionsForType, $type, $types)) {
            $account->setType($types[$type]);
        } else {
            throw new RestException("Unrecognized type for type conversion or conversion not allowed:" . $type);
        }
    }

    /**
     * Checks whether transition from one type to another is allowed
     * @param $transitionsForType
     * @param $newAccountType
     * @param $types
     * @return bool
     */
    protected function isTransitionAllowed($transitionsForType, $newAccountType, $types)
    {
        foreach ($transitionsForType as $trans) {
            if ($trans === intval($types[$newAccountType])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns valid transitions for a specific accoun type
     * @param $config
     * @param $types
     * @param $accountTypeName
     * @return array
     */
    protected function getAccountTypeTransitions($config, $types, $accountTypeName)
    {
        $transitions = [];
        foreach ($config[$accountTypeName]['convertableTo'] as $transTypeKey => $transTypeValue) {
            if (!!$transTypeValue) {
                $transitions[] = $types[$transTypeKey];
            }
        }

        return $transitions;
    }

    /**
     * Gets the account types and their numeric representation
     * @param $config
     * @return array
     */
    protected function getAccountTypes($config)
    {
        $types = [];
        foreach ($config as $confType) {
            $types[$confType['name']] = $confType['id'];
        }
        return $types;
    }


    /**
     * Process all addresses from request
     * @param Account $contact The contact on which is worked
     * @param $addresses
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processAccountAddresses($contact, $addresses)
    {
        $getAddressId = function($accountAddress) {
            return $accountAddress->getAddress()->getId();
        };

        $delete = function ($accountAddress) use ($contact) {
            $address = $accountAddress->getAddress();
            $this->getContactManager()->removeAddress($contact, $address);
            return true;
        };

        $update = function ($accountAddress, $matchedEntry) use ($contact) {
            $address = $accountAddress->getAddress();
            $result = $this->updateAddress($address, $matchedEntry, $isMain);
            if ($isMain) {
                $this->getContactManager()->unsetMain($this->getContactManager()->getAddressRelations($contact));
            }
            $accountAddress->setMain($isMain);

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

    /**
     * @return ContactManagerInterface
     */
    protected function getContactManager() {
        return $this->get('sulu_contact.account_manager');
    }
}
