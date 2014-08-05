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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery;
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use \DateTime;
use Symfony\Component\HttpFoundation\Request;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Rest\ListBuilder\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;

/**
 * Makes accounts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class AccountController extends AbstractContactController
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluContactBundle:Account';
    protected static $entityKey = 'accounts';
    protected static $positionEntityName = 'SuluContactBundle:Position';
    protected static $contactEntityKey = 'contacts';
    protected static $contactEntityName = 'SuluContactBundle:Contact';
    protected static $accountCategoryEntityName = 'SuluContactBundle:AccountCategory';
    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected static $termsOfPaymentEntityName = 'SuluContactBundle:TermsOfPayment';
    protected static $termsOfDeliveryEntityName = 'SuluContactBundle:TermsOfDelivery';
    protected static $emailEntityName = 'SuluContactBundle:Email';
    protected static $phoneEntityName = 'SuluContactBundle:Phone';
    protected static $urlEntityName = 'SuluContactBundle:Url';
    protected static $faxEntityName = 'SuluContactBundle:Fax';
    protected static $addressEntityName = 'SuluContactBundle:Address';
    protected static $accountAddressEntityName = 'SuluContactBundle:AccountAddress';

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.accounts.';

    // TODO: Move the field descriptors to a manager
    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;
    protected $accountContactFieldDescriptors;

    // TODO: move the field descriptors to a manager
    public function __construct()
    {
        $this->fieldDescriptors = array();
        $this->initAccountContactFieldDescriptors();

        $this->fieldDescriptors['number'] = new DoctrineFieldDescriptor(
            'number',
            'number',
            self::$entityName,
            'contact.accounts.number',
            array(),
            false,
            false,
            '',
            '90px'
        );

        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$entityName,
            'public.name',
            array(),
            false,
            true,
            '',
            '300px'
        );

        $this->fieldDescriptors['corporation'] = new DoctrineFieldDescriptor(
            'corporation',
            'corporation',
            self::$entityName,
            'contact.accounts.corporation',
            array(),
            true,
            false
        );

        $this->fieldDescriptors['city'] = new DoctrineFieldDescriptor(
            'city',
            'city',
            self::$addressEntityName,
            'contact.address.city',
            array(
                self::$accountAddressEntityName => new DoctrineJoinDescriptor(
                        self::$accountAddressEntityName,
                        self::$entityName .
                        '.accountAddresses',
                        self::$accountAddressEntityName . '.main = true', DoctrineJoinDescriptor::JOIN_METHOD_LEFT
                    ),
                self::$addressEntityName => new DoctrineJoinDescriptor(
                        self::$addressEntityName,
                        self::$accountAddressEntityName . '.address'
                    )
            ),
            false,
            true,
            true
        );

        $this->fieldDescriptors['mainContact'] = new DoctrineConcatenationFieldDescriptor(
            array(
                new DoctrineFieldDescriptor(
                    'firstName',
                    'mainContact',
                    self::$contactEntityName,
                    'contact.contacts.main-contact',
                    array(
                        self::$contactEntityName => new DoctrineJoinDescriptor(
                                self::$contactEntityName,
                                self::$entityName .
                                '.mainContact'
                            )
                    )
                ),
                new DoctrineFieldDescriptor(
                    'lastName',
                    'mainContact',
                    self::$contactEntityName,
                    'contact.contacts.main-contact',
                    array(
                        self::$contactEntityName => new DoctrineJoinDescriptor(
                                self::$contactEntityName,
                                self::$entityName .
                                '.mainContact'
                            )
                    )
                )
            ),
            'mainContact',
            'contact.contacts.main-contact',
            ' ',
            false,
            true,
            '',
            '200px'
        );

        $this->fieldDescriptors['mainPhone'] = new DoctrineFieldDescriptor(
            'mainPhone',
            'mainPhone',
            self::$entityName,
            'public.phone'
        );

        $this->fieldDescriptors['mainEmail'] = new DoctrineFieldDescriptor(
            'mainEmail',
            'mainEmail',
            self::$entityName,
            'public.email',
            array(),
            false,
            true,
            '',
            '',
            '140px'
        );

        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName,
            'public.id',
            array(),
            true,
            false,
            '',
            '50px'
        );

        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::$entityName,
            'public.created',
            array(),
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::$entityName,
            'public.changed',
            array(),
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['type'] = new DoctrineFieldDescriptor(
            'type',
            'type',
            self::$entityName,
            'contact.accounts.type',
            array(),
            true,
            false,
            '',
            '150px'
        );

        $this->fieldDescriptors['disabled'] = new DoctrineFieldDescriptor(
            'disabled',
            'disabled',
            self::$entityName,
            'public.locked',
            array(),
            true
        );

        $this->fieldDescriptors['uid'] = new DoctrineFieldDescriptor(
            'uid',
            'uid',
            self::$entityName,
            'contact.accounts.uid',
            array(),
            true
        );

        $this->fieldDescriptors['registerNumber'] = new DoctrineFieldDescriptor(
            'registerNumber',
            'registerNumber',
            self::$entityName,
            'contact.accounts.registerNumber',
            array(),
            true
        );

        $this->fieldDescriptors['mainFax'] = new DoctrineFieldDescriptor(
            'mainFax',
            'mainFax',
            self::$entityName,
            'public.phone',
            array(),
            true,
            false
        );

        $this->fieldDescriptors['mainUrl'] = new DoctrineFieldDescriptor(
            'mainUrl',
            'mainUrl',
            self::$entityName,
            'public.url',
            array(),
            true,
            false
        );

        $this->fieldDescriptors['placeOfJurisdiction'] = new DoctrineFieldDescriptor(
            'placeOfJurisdiction',
            'placeOfJurisdiction',
            self::$entityName,
            'contact.accounts.placeOfJurisdiction',
            array(),
            true
        );
    }

    /**
     * returns all fields that can be used by list
     * @return mixed
     */
    public function fieldsAction()
    {
        // default contacts list
        return $this->handleView($this->view(array_values($this->fieldDescriptors), 200));
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
                    ->getRepository(self::$entityName)
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

            /* @var Account $account */
            $account = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->find($id);

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->getRestHelper();

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->accountContactFieldDescriptors);

            $listBuilder->where($this->fieldDescriptors['id'], $id);

            // FIXME could be removed when field descriptor with expression is implemented and used
            $values = $listBuilder->execute();

            foreach($values as &$value){
                if($account->getMainContact() != null && $value['id'] === $account->getMainContact()->getId()){
                    $value['isMainContact'] = true;
                } else {
                    $value['isMainContact'] = false;
                }
            }

            $list = new ListRepresentation(
                $values,
                'contacts',
                'get_account_contacts',
                array_merge(array('id' => $id), $request->query->all()),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );

        } else {
            $contacts = $this->getDoctrine()->getRepository(self::$contactEntityName)->findByAccountId($id);
            $list = new CollectionRepresentation($contacts, self::$contactEntityKey);
        }
        $view = $this->view($list, 200);
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
                ->getRepository(self::$entityName)
                ->find($accountId);
            if (!$account) {
                throw new EntityNotFoundException('account', $accountId);
            }

            // get contact
            $contact = $this->getDoctrine()
                ->getRepository(self::$contactEntityName)
                ->find($contactId);
            if (!$contact) {
                throw new EntityNotFoundException('contact', $contactId);
            }

            // check if relation already exists
            $accountContact = $this->getDoctrine()
                ->getRepository(self::$accountContactEntityName)
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

            // Set position on contact
            $position = $this->getPosition($request->get('position', null));
            $accountContact->setPosition($position);
            $contact->setCurrentPosition($position);

            $em = $this->getDoctrine()->getManager();
            $em->persist($accountContact);
            $em->flush();

            $isMainContact = false;
            if ($account->getMainContact()) {
                $isMainContact = $account->getMainContact()->getId() === $contact->getId();
            }

            $contactArray = array(
                'id' => $contact->getId(),
                'fullName' => $contact->getFullName(),
                'isMainContact' => $isMainContact
            );

            if($position){
                $contactArray['position'] = $position->getPosition();
            }

            $view = $this->view($contactArray, 200);
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
            /** @var AccountContact $accountContact */
            $accountContact = $this->getDoctrine()
                ->getRepository(self::$accountContactEntityName)
                ->findByForeignIds($accountId, $contactId);

            if (!$accountContact) {
                throw new EntityNotFoundException('AccountContact', $accountId . $contactId);
            }
            $id = $accountContact->getId();

            $account = $accountContact->getAccount();

            // remove main contact when relation with main was removed
            if($account->getMainContact() && strval($account->getMainContact()->getId()) === $contactId){
                $account->setMainContact(null);
            }

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
        $type = $request->get('type');

        if ($request->get('flat') == 'true') {

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            if ($type) {
                $listBuilder->where($this->fieldDescriptors['type'], $type);
            }

            $restHelper->initializeListBuilder($listBuilder, $this->fieldDescriptors);

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_accounts',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $contacts = $this->getDoctrine()->getRepository(self::$entityName)->findAll();
            $list = new CollectionRepresentation($contacts, self::$entityKey);
        }

        $view = $this->view($list, 200);
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

            $this->setResponsiblePerson($em, $account, $request->get('responsiblePerson'));

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
            $this->addNewContactRelations($account, $request);

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

    private function setResponsiblePerson(ObjectManager $em, Account $account, $responsiblePerson)
    {
        if (!!$responsiblePerson) {
            $id = $responsiblePerson['id'];
            /* @var Contact $contact */
            $contact = $em->getRepository(self::$contactEntityName)->find($id);

            if (!$contact) {
                throw new EntityNotFoundException(self::$contactEntityName, $id);
            }
            $account->setResponsiblePerson($contact);
        }
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
        try {
            /** @var Account $account */
            $account = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException(self::$entityName, $id);
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

                $this->setResponsiblePerson($em, $account, $request->get('responsiblePerson'));

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
                if (!($this->processUrls($account, $request->get('urls', array()))
                    && $this->processEmails($account, $request->get('emails', array()))
                    && $this->processFaxes($account, $request->get('faxes', array()))
                    && $this->processPhones($account, $request->get('phones', array()))
                    && $this->processAddresses($account, $request->get('addresses', array()))
                    && $this->processTags($account, $request->get('tags', array()))
                    && $this->processNotes($account, $request->get('notes', array())))
                ) {
                    throw new RestException('Updating dependencies is not possible', 0);
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
                ->getRepository(self::$entityName)
                ->findAccountById($parentData['id']);
            if (!$parent) {
                throw new EntityNotFoundException(self::$entityName, $parentData['id']);
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
            $category = $this->getDoctrine()->getRepository(self::$accountCategoryEntityName)->find($categoryId);
            if (!is_null($category)) {
                $account->setAccountCategory($category);
            } else {
                throw new EntityNotFoundException(self::$accountCategoryEntityName, $categoryId);
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
        $em = $this->getDoctrine()->getManager();

        try {
            /** @var Account $account */
            $account = $em->getRepository(self::$entityName)
                ->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException(self::$entityName, $id);
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
                    $mainContact = $em->getRepository(self::$contactEntityName)->find($mainContactRequest['id']);
                    if ($mainContact) {
                        $account->setMainContact($mainContact);
                    }
                }

                if(!is_null($request->get('medias'))) {
                    $this->processMedias($account, $request->get('medias', array()));
                }

                // process details
                if (!is_null($request->get('bankAccounts'))) {
                    $this->processBankAccounts($account, $request->get('bankAccounts', array()));
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
                ->getRepository(self::$termsOfPaymentEntityName)
                ->find($id);

            if (!$termsOfPayment) {
                throw new EntityNotFoundException(self::$termsOfPaymentEntityName, $id);
            }
            $account->setTermsOfPayment($termsOfPayment);
        }

        if (!is_null($request->get('termsOfDelivery'))) {
            $id = $request->get('termsOfDelivery')['id'];
            /** @var TermsOfDelivery $termsOfDelivery */
            $termsOfDelivery = $this->getDoctrine()
                ->getRepository(self::$termsOfDeliveryEntityName)
                ->find($id);
            if (!$termsOfDelivery) {
                throw new EntityNotFoundException(self::$termsOfDeliveryEntityName, $id);
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
            /* @var Account $account */
            $account = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->findAccountByIdAndDelete($id);

            if (!$account) {
                throw new EntityNotFoundException(self::$entityName, $id);
            }

            // do not allow to delete entity if child is existent
            if (!$account->getChildren()->count()) {
                // return 405 error
            }

            $em = $this->getDoctrine()->getManager();

            $addresses = $account->getAddresses();
            /** @var Address $address */
            foreach ($addresses as $address) {
                if (!$address->hasRelations()) {
                    $em->remove($address);
                }
            }

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
                ->getRepository(self::$entityName)
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
            ->getRepository(self::$entityName)
            ->findChildrenAndContacts($id);

        if ($account != null) {
            // return a maximum of 3 accounts
            $slicedContacts = array();
            $accountContacts = $account->getAccountContacts();
            $numContacts = 0;
            if (!is_null($accountContacts)) {
                foreach ($accountContacts as $accountContact) {
                    /** @var AccountContact $accountContact */
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
                        ->getRepository(self::$entityName)
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
     * @return AbstractContactManager
     */
    protected function getContactManager()
    {
        return $this->get('sulu_contact.account_manager');
    }

    /**
     * Inits the account contact descriptors
     */
    protected function initAccountContactFieldDescriptors()
    {
        $this->accountContactFieldDescriptors = array();
        $contactJoin = array(
            self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    self::$entityName . '.accountContacts',
                    null,
                    DoctrineJoinDescriptor::JOIN_METHOD_INNER
                ),
            self::$contactEntityName => new DoctrineJoinDescriptor(
                    self::$contactEntityName,
                    self::$accountContactEntityName . '.contact'
                )
        );

        $this->accountContactFieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$contactEntityName,
            'contact.contacts.main-contact',
            $contactJoin,
            false,
            false,
            '',
            '',
            '',
            false
        );

        $this->accountContactFieldDescriptors['fullName'] = new DoctrineConcatenationFieldDescriptor(
            array(
                new DoctrineFieldDescriptor(
                    'firstName',
                    'mainContact',
                    self::$contactEntityName,
                    'contact.contacts.main-contact',
                    $contactJoin
                ),
                new DoctrineFieldDescriptor(
                    'lastName',
                    'mainContact',
                    self::$contactEntityName,
                    'contact.contacts.main-contact',
                    $contactJoin
                )
            ),
            'fullName',
            'public.name',
            ' ',
            false,
            true,
            '',
            '',
            '160px'
        );

        $this->accountContactFieldDescriptors['position'] = new DoctrineFieldDescriptor(
            'position',
            'position',
            self::$positionEntityName,
            'contact.contacts.position',
            array(
                self::$positionEntityName => new DoctrineJoinDescriptor(
                        self::$positionEntityName,
                        self::$accountContactEntityName . '.position'
                    )
            ),
            false,
            true
        );

        // FIXME use field descriptor with expression when implemented
        $this->accountContactFieldDescriptors['isMainContact'] = new DoctrineFieldDescriptor(
            'main',
            'isMainContact',
            self::$accountContactEntityName,
            'contact.contacts.main-contact',
            array(
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                        self::$accountContactEntityName,
                        self::$entityName . '.accountContacts',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
            ),
            false,
            true,
            'radio'
        );
    }

}
