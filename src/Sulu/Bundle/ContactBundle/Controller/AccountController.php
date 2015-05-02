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
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes accounts available through a REST API
 */
class AccountController extends AbstractContactController implements SecuredControllerInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'accounts';
    protected static $positionEntityName = 'SuluContactBundle:Position';
    protected static $contactEntityKey = 'contacts';
    protected static $contactEntityName = 'SuluContactBundle:Contact';
    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected static $emailEntityName = 'SuluContactBundle:Email';
    protected static $phoneEntityName = 'SuluContactBundle:Phone';
    protected static $urlEntityName = 'SuluContactBundle:Url';
    protected static $faxEntityName = 'SuluContactBundle:Fax';
    protected static $addressEntityName = 'SuluContactBundle:Address';
    protected static $accountAddressEntityName = 'SuluContactBundle:AccountAddress';
    protected static $countryEntityName = 'SuluContactBundle:Country';

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.accounts.';

    /**
     * @var AccountManager
     */
    protected $accountManager;
    protected $locale;

    // TODO: Move the field descriptors to a manager
    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;
    protected $accountContactFieldDescriptors;
    protected $accountAddressesFieldDescriptors;

    /**
     * returns all fields that can be used by list
     *
     * @return mixed
     */
    public function fieldsAction()
    {
        // default contacts list
        return $this->handleView($this->view(array_values($this->getFieldDescriptors()), 200));
    }

    /**
     * Shows a single account with the given id
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $includes = explode(',', $request->get('include'));
        $accountManager = $this->getContactManager();
        $locale = $this->getUser()->getLocale();

        try {
            $view = $this->responseGetById(
                $id,
                function ($id) use ($includes, $accountManager, $locale) {
                    return $accountManager->getByIdAndInclude($id, $locale, $includes);
                }
            );

            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    array('fullAccount', 'partialContact', 'partialMedia', 'partialTag', 'fullCategory')
                )
            );
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * lists all contacts of an account
     * optional parameter 'flat' calls listAction
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getContactsAction($id, Request $request)
    {
        if ($request->get('flat') == 'true') {

            /* @var AccountInterface $account */
            $account = $this->getDoctrine()
                ->getRepository($this->getAccountEntityName())
                ->find($id);

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->getRestHelper();

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create($this->getAccountEntityName());

            $restHelper->initializeListBuilder($listBuilder, $this->getAccountContactFieldDescriptors());

            $listBuilder->where($this->getFieldDescriptors()['id'], $id);

            // FIXME could be removed when field descriptor with expression is implemented and used
            $values = $listBuilder->execute();

            foreach ($values as &$value) {
                if ($account->getMainContact() != null && $value['id'] === $account->getMainContact()->getId()) {
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
            $contactManager = $this->getContactManager();
            $locale = $this->getUser()->getLocale();
            $contacts = $contactManager->findContactsByAccountId($id, $locale, false);
            $list = new CollectionRepresentation($contacts, self::$contactEntityKey);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * lists all addresses of an account
     * optional parameter 'flat' calls listAction
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAddressesAction($id, Request $request)
    {
        if ($request->get('flat') == 'true') {

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->getRestHelper();

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create($this->getAccountEntityName());

            $restHelper->initializeListBuilder($listBuilder, $this->getAccountAddressesFieldDescriptors());

            $listBuilder->where($this->getFieldDescriptors()['id'], $id);

            $values = $listBuilder->execute();

            $list = new ListRepresentation(
                $values,
                'addresses',
                'get_account_addresses',
                array_merge(array('id' => $id), $request->query->all()),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );

        } else {
            $addresses = $this->getDoctrine()->getRepository(self::$addressEntityName)->findByAccountId($id);
            $list = new CollectionRepresentation($addresses, 'addresses');
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
            /** @var AccountInterface $account */
            $account = $this->getDoctrine()
                ->getRepository($this->getAccountEntityName())
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
            $accountContact = new AccountContactEntity();
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

            if ($position) {
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
     *
     * @param $accountId
     * @param $contactId
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function deleteContactsAction($accountId, $contactId)
    {
        try {
            // check if relation exists
            /** @var AccountContactEntity $accountContact */
            $accountContact = $this->getDoctrine()
                ->getRepository(self::$accountContactEntityName)
                ->findByForeignIds($accountId, $contactId);

            if (!$accountContact) {
                throw new EntityNotFoundException('AccountContact', $accountId . $contactId);
            }
            $id = $accountContact->getId();

            $account = $accountContact->getAccount();

            // remove main contact when relation with main was removed
            if ($account->getMainContact() && strval($account->getMainContact()->getId()) === $contactId) {
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
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        // define filters
        $filter = array();
        $ids = $request->get('ids');
        if ($ids) {
            if (is_array($ids)) {
                $filter['id'] = $ids;
            } else {
                $filter['id'] = explode(',', $ids);
            }
        }

        if ($request->get('flat') == 'true') {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            $listBuilder = $this->generateFlatListBuilder($request, $filter);
            $restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_accounts',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
            $view = $this->view($list, 200);
        } else {
            $accountManager = $this->getContactManager();
            $locale = $this->getUser()->getLocale();
            $accounts = $accountManager->findAll($locale, $filter);
            $list = new CollectionRepresentation($accounts, self::$entityKey);
            $view = $this->view($list, 200);
//          // FIXME: add serialization context for collection
//            $view->setSerializationContext(
//                SerializationContext::create()->setGroups(array('fullAccount', 'partialContact', 'partialMedia'))
//            );
        }

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @param $filter
     */
    protected function generateFlatListBuilder(Request $request, $filter)
    {

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $listBuilder = $factory->create($this->getAccountEntityName());

        if (json_decode($request->get('hasNoParent', null))) {
            $listBuilder->where($this->getFieldDescriptorForNoParent(), null);
        }

        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $listBuilder->in($this->getFieldDescriptors()[$key], $value);
            } else {
                $listBuilder->where($this->getFieldDescriptors()[$key], $value);
            }
        }

        return $listBuilder;
    }

    /**
     * Returns fielddescriptor used for checking if account has no parent
     * Will result in an error when added to the array of fielddescriptors
     * because its just for checking if parent exists or not and does not
     * point to a property of the parent
     * @return DoctrineFieldDescriptor
     */
    protected function getFieldDescriptorForNoParent()
    {
        return new DoctrineFieldDescriptor(
            'parent',
            'parent',
            $this->getAccountEntityName(),
            'contact.accounts.company',
            array(),
            true,
            false
        );
    }

    /**
     * Creates a new account
     *
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

            $account = $this->doPost($request);

            $em->persist($account);

            $em->flush();

            // get api entity
            $accountManager = $this->getContactManager();
            $locale = $this->getUser()->getLocale();
            $acc = $accountManager->getAccount($account, $locale);
            $view = $this->view($acc, 200);
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(array('fullAccount', 'partialContact', 'partialMedia'))
            );

        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * maps data from request to a new account
     * @param Request $request
     * @return AccountInterface
     * @throws EntityNotFoundException
     */
    protected function doPost(Request $request)
    {
        $account = $this->get('sulu_contact.account_factory')->createEntity();

        $account->setName($request->get('name'));

        $account->setCorporation($request->get('corporation'));

        if ($request->get('uid') !== null) {
            $account->setUid($request->get('uid'));
        }

        $disabled = $request->get('disabled');
        if ($disabled === null) {
            $disabled = false;
        }
        $account->setDisabled($disabled);

        // set parent
        $this->setParent($request->get('parent'), $account);

        // process categories
        $this->processCategories($account, $request->get('categories', array()));

        // set creator / changer
        $account->setCreator($this->getUser());
        $account->setChanger($this->getUser());

        // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
        $this->addNewContactRelations($account, $request);

        return $account;
    }

    /**
     * Edits the existing contact with the given id
     *
     * @param integer $id The id of the contact to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        try {
            /** @var AccountInterface $account */
            $account = $this->getDoctrine()
                ->getRepository($this->getAccountEntityName())
                ->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            } else {

                $em = $this->getDoctrine()->getManager();

                $this->doPut($account, $request);

                $em->flush();

                // get api entity
                $accountManager = $this->getContactManager();
                $locale = $this->getUser()->getLocale();
                $acc = $accountManager->getAccount($account, $locale);

                $view = $this->view($acc, 200);
                $view->setSerializationContext(
                    SerializationContext::create()->setGroups(array('fullAccount', 'partialContact', 'partialMedia'))
                );
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * processes given entity for put
     * @param AccountInterface $account
     * @param Request $request
     * @throws EntityNotFoundException
     * @throws RestException
     */
    protected function doPut(AccountInterface $account, Request $request)
    {
        // set name
        $account->setName($request->get('name'));
        $account->setCorporation($request->get('corporation'));

        // set disabled
        $disabled = $request->get('disabled');
        if ($disabled !== null) {
            $account->setDisabled($disabled);
        }

        if ($request->get('uid') !== null) {
            $account->setUid($request->get('uid'));
        }

        // set parent
        $this->setParent($request->get('parent'), $account);

        // set changed
        $user = $this->getUser();
        $account->setChanger($user);

        // process details
        if (!($this->processUrls($account, $request->get('urls', array()))
            && $this->processEmails($account, $request->get('emails', array()))
            && $this->processFaxes($account, $request->get('faxes', array()))
            && $this->processPhones($account, $request->get('phones', array()))
            && $this->processAddresses($account, $request->get('addresses', array()))
            && $this->processTags($account, $request->get('tags', array()))
            && $this->processNotes($account, $request->get('notes', array()))
            && $this->processCategories($account, $request->get('categories', array()))
            && $this->processBankAccounts($account, $request->get('bankAccounts', array())))
        ) {
            throw new RestException('Updating dependencies is not possible', 0);
        }
    }

    /**
     * set parent to account
     *
     * @param array $parentData
     * @param AccountInterface $account
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function setParent($parentData, AccountInterface $account)
    {
        if ($parentData != null && isset($parentData['id']) && $parentData['id'] != 'null' && $parentData['id'] != '') {
            $parent = $this->getDoctrine()
                ->getRepository($this->getAccountEntityName())
                ->findAccountById($parentData['id']);
            if (!$parent) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $parentData['id']);
            }
            $account->setParent($parent);
        } else {
            $account->setParent(null);
        }
    }

    /**
     * partial update of account infos
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        try {
            /** @var AccountInterface $account */
            $account = $em->getRepository($this->getAccountEntityName())
                ->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            } else {
                $this->doPatch($account, $request, $em);

                $em->flush();

                // get api entity
                $accountManager = $this->getContactManager();
                $locale = $this->getUser()->getLocale();
                $acc = $accountManager->getAccount($account, $locale);

                $view = $this->view($acc, 200);
                $view->setSerializationContext(
                    SerializationContext::create()->setGroups(array('fullAccount', 'partialContact', 'partialMedia'))
                );
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * process geiven entity for patch
     * @param AccountInterface $account
     * @param Request $request
     * @param ObjectManager $entityManager
     */
    protected function doPatch(AccountInterface $account, Request $request, ObjectManager $entityManager)
    {
        if ($request->get('uid') !== null) {
            $account->setUid($request->get('uid'));
        }
        if ($request->get('registerNumber') !== null) {
            $account->setRegisterNumber($request->get('registerNumber'));
        }
        if ($request->get('number') !== null) {
            $account->setNumber($request->get('number'));
        }

        if ($request->get('placeOfJurisdiction') !== null) {
            $account->setPlaceOfJurisdiction($request->get('placeOfJurisdiction'));
        }

        // check if mainContact is set
        if (($mainContactRequest = $request->get('mainContact')) !== null) {
            $mainContact = $entityManager->getRepository(self::$contactEntityName)->find($mainContactRequest['id']);
            if ($mainContact) {
                $account->setMainContact($mainContact);
            }
        }

        // process details
        if ($request->get('bankAccounts') !== null) {
            $this->processBankAccounts($account, $request->get('bankAccounts', array()));
        }
    }

    /**
     * Delete an account with the given id
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, Request $request)
    {
        $delete = function ($id) use ($request) {
            /* @var AccountInterface $account */
            $account = $this->getDoctrine()
                ->getRepository($this->getAccountEntityName())
                ->findAccountByIdAndDelete($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            }

            // do not allow to delete entity if child is existent
            if (!$account->getChildren()->count()) {
                // return 405 error
            }

            $em = $this->getDoctrine()->getManager();

            $addresses = $account->getAddresses();
            /** @var AddressEntity $address */
            foreach ($addresses as $address) {
                if (!$address->hasRelations()) {
                    $em->remove($address);
                }
            }

            // remove related contacts if removeContacts is true
            if ($request->get('removeContacts') !== null &&
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
     *
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
            /** @var AccountInterface $account */
            $account = $this->getDoctrine()
                ->getRepository($this->getAccountEntityName())
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
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDeleteinfoAction($id)
    {
        $response = array();
        $response['contacts'] = array();

        /** @var AccountInterface $account */
        $account = $this->getDoctrine()
            ->getRepository($this->getAccountEntityName())
            ->findChildrenAndContacts($id);

        if ($account != null) {
            // return a maximum of 3 accounts
            $slicedContacts = array();
            $accountContacts = $account->getAccountContacts();
            $numContacts = 0;
            if ($accountContacts !== null) {
                foreach ($accountContacts as $accountContact) {
                    /** @var AccountContactEntity $accountContact */
                    $contactId = $accountContact->getContact()->getId();
                    if (!array_key_exists($contactId, $slicedContacts)) {
                        if ($numContacts++ < 3) {
                            $slicedContacts[$contactId] = $accountContact->getContact();
                        }
                    }
                }
            }

            foreach ($slicedContacts as $contact) {
                /** @var ContactEntity $contact */
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

                /* @var AccountInterface $sc */
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
     * @return AbstractContactManager
     */
    protected function getContactManager()
    {
        return $this->get('sulu_contact.account_manager');
    }

    protected function getFieldDescriptors()
    {
        if ($this->fieldDescriptors === null) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    protected function getAccountContactFieldDescriptors()
    {
        if ($this->accountContactFieldDescriptors === null) {
            $this->initAccountContactFieldDescriptors();
        }

        return $this->accountContactFieldDescriptors;
    }

    protected function getAccountAddressesFieldDescriptors()
    {
        if ($this->accountAddressesFieldDescriptors === null) {
            $this->initAccountAddressesFieldDescriptors();
        }

        return $this->accountAddressesFieldDescriptors;
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
                $this->getAccountEntityName() . '.accountContacts',
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
            '160px',
            false
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
                    $this->getAccountEntityName() . '.accountContacts',
                    null,
                    DoctrineJoinDescriptor::JOIN_METHOD_INNER
                ),
            ),
            false,
            true,
            'radio'
        );
    }

    /**
     * Inits the account contact descriptors
     */
    protected function initAccountAddressesFieldDescriptors()
    {
        $this->accountAddressesFieldDescriptors = array();

        $addressJoin = array(
            self::$accountAddressEntityName => new DoctrineJoinDescriptor(
                self::$accountAddressEntityName,
                $this->getAccountEntityName() . '.accountAddresses'
            ),
            self::$addressEntityName => new DoctrineJoinDescriptor(
                self::$addressEntityName,
                self::$accountAddressEntityName . '.address'
            )
        );
        $countryJoin = array(
            self::$countryEntityName => new DoctrineJoinDescriptor(
                self::$countryEntityName,
                self::$addressEntityName . '.country'
            ),
            self::$accountAddressEntityName => new DoctrineJoinDescriptor(
                self::$accountAddressEntityName,
                $this->getAccountEntityName() . '.accountAddresses'
            ),
            self::$addressEntityName => new DoctrineJoinDescriptor(
                self::$addressEntityName,
                self::$accountAddressEntityName . '.address'
            )
        );

        $this->accountAddressesFieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$addressEntityName,
            'contact.contacts.address',
            $addressJoin,
            false,
            false,
            '',
            '',
            '',
            false
        );

        $this->accountAddressesFieldDescriptors['address'] = new DoctrineConcatenationFieldDescriptor(
            array(
                new DoctrineConcatenationFieldDescriptor(
                    array(
                        new DoctrineFieldDescriptor(
                            'street',
                            'address',
                            self::$addressEntityName,
                            '',
                            $addressJoin
                        ),
                        new DoctrineFieldDescriptor(
                            'number',
                            'address',
                            self::$addressEntityName,
                            '',
                            $addressJoin
                        ),
                        new DoctrineFieldDescriptor(
                            'addition',
                            'address',
                            self::$addressEntityName,
                            '',
                            $addressJoin
                        ),
                    ),
                    'street',
                    ' '
                ),
                new DoctrineFieldDescriptor(
                    'zip',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
                new DoctrineFieldDescriptor(
                    'city',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
                new DoctrineFieldDescriptor(
                    'state',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
                new DoctrineFieldDescriptor(
                    'name',
                    'address',
                    self::$countryEntityName,
                    '',
                    $countryJoin
                ),
                new DoctrineFieldDescriptor(
                    'postboxNumber',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
            ),
            'address',
            'public.address',
            ', ',
            false,
            true,
            '',
            '',
            '300px'
        );
    }

    protected function initFieldDescriptors()
    {

        $this->fieldDescriptors = array();
        $this->fieldDescriptors['number'] = new DoctrineFieldDescriptor(
            'number',
            'number',
            $this->getAccountEntityName(),
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
            $this->getAccountEntityName(),
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
            $this->getAccountEntityName(),
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
                    $this->getAccountEntityName() .
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
                            $this->getAccountEntityName() .
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
                            $this->getAccountEntityName() .
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
            '200px',
            '',
            false
        );

        $this->fieldDescriptors['mainPhone'] = new DoctrineFieldDescriptor(
            'mainPhone',
            'mainPhone',
            $this->getAccountEntityName(),
            'public.phone'
        );

        $this->fieldDescriptors['mainEmail'] = new DoctrineFieldDescriptor(
            'mainEmail',
            'mainEmail',
            $this->getAccountEntityName(),
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
            $this->getAccountEntityName(),
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
            $this->getAccountEntityName(),
            'public.created',
            array(),
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            $this->getAccountEntityName(),
            'public.changed',
            array(),
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['disabled'] = new DoctrineFieldDescriptor(
            'disabled',
            'disabled',
            $this->getAccountEntityName(),
            'public.locked',
            array(),
            true
        );

        $this->fieldDescriptors['uid'] = new DoctrineFieldDescriptor(
            'uid',
            'uid',
            $this->getAccountEntityName(),
            'contact.accounts.uid',
            array(),
            true
        );

        $this->fieldDescriptors['registerNumber'] = new DoctrineFieldDescriptor(
            'registerNumber',
            'registerNumber',
            $this->getAccountEntityName(),
            'contact.accounts.registerNumber',
            array(),
            true
        );

        $this->fieldDescriptors['mainFax'] = new DoctrineFieldDescriptor(
            'mainFax',
            'mainFax',
            $this->getAccountEntityName(),
            'public.phone',
            array(),
            true,
            false
        );

        $this->fieldDescriptors['mainUrl'] = new DoctrineFieldDescriptor(
            'mainUrl',
            'mainUrl',
            $this->getAccountEntityName(),
            'public.url',
            array(),
            true,
            false
        );

        $this->fieldDescriptors['placeOfJurisdiction'] = new DoctrineFieldDescriptor(
            'placeOfJurisdiction',
            'placeOfJurisdiction',
            $this->getAccountEntityName(),
            'contact.accounts.placeOfJurisdiction',
            array(),
            true
        );
    }

    protected function getAccountEntityName()
    {
        return $this->container->getParameter('sulu_contact.account.entity');
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.contact.organizations';
    }
}
