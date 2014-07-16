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
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Rest\ListBuilder\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Makes contacts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class ContactController extends AbstractContactController
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluContactBundle:Contact';
    protected static $entityKey = 'contacts';
    protected static $accountEntityName = 'SuluContactBundle:Account';
    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected static $titleEntityName = 'SuluContactBundle:Title';
    protected static $addressEntityName = 'SuluContactBundle:Address';
    protected static $contactAddressEntityName = 'SuluContactBundle:ContactAddress';

    /**
     * @var string
     */
    protected $basePath = 'admin/api/contacts';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array('account', 'accountContacts_position', 'city');

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
    protected $fieldsHidden = array(
        'middleName',
        'created',
        'changed',
        'birthday',
        'salutation',
        'formOfAddress',
        'id',
        'title',
        'disabled',
        'mainUrl',
        'mainFax',
        'accountContacts_position',
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array(
        'account',
        'city',
        'accountContacts_position',
    );

    protected $fieldsWidth = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(
        1 => 'account',
        2 => 'firstName',
        3 => 'middleName',
        4 => 'lastName',
        5 => 'city',
        6 => 'mainPhone',
        7 => 'mainEmail',
        10 => 'title',
        20 => 'id',
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
        'isMainContact' => 'contact.contacts.main-contact',
        'mainEmail' => 'public.email',
        'mainPhone' => 'public.phone',
        'mainUrl' => 'public.url',
        'mainFax' => 'public.fax',
        'city' => 'contact.address.city',
    );

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.contacts.';

    /**
     * TODO: move the field descriptors to a manager
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    /**
     * TODO: move field descriptors to a manager
     */
    public function __construct() {
        $this->fieldDescriptors = array();
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName
        );
        $this->fieldDescriptors['mainPhone'] = new DoctrineFieldDescriptor(
            'mainPhone',
            'mainPhone',
            self::$entityName
        );
        $this->fieldDescriptors['mainFax'] = new DoctrineFieldDescriptor(
            'mainFax',
            'mainFax',
            self::$entityName
        );
        $this->fieldDescriptors['mainUrl'] = new DoctrineFieldDescriptor(
            'mainUrl',
            'mainUrl',
            self::$entityName
        );
        $this->fieldDescriptors['mainEmail'] = new DoctrineFieldDescriptor(
            'mainEmail',
            'mainEmail',
            self::$entityName
        );
        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::$entityName
        );
        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::$entityName
        );
        $this->fieldDescriptors['disabled'] = new DoctrineFieldDescriptor(
            'disabled',
            'disabled',
            self::$entityName
        );
        $this->fieldDescriptors['birthday'] = new DoctrineFieldDescriptor(
            'birthday',
            'birthday',
            self::$entityName
        );
        $this->fieldDescriptors['salutation'] = new DoctrineFieldDescriptor(
            'salutation',
            'salutation',
            self::$entityName
        );
        $this->fieldDescriptors['formOfAddress'] = new DoctrineFieldDescriptor(
            'formOfAddress',
            'formOfAddress',
            self::$entityName
        );
        $this->fieldDescriptors['firstName'] = new DoctrineFieldDescriptor(
            'firstName',
            'firstName',
            self::$entityName
        );
        $this->fieldDescriptors['middleName'] = new DoctrineFieldDescriptor(
            'middleName',
            'middleName',
            self::$entityName
        );
        $this->fieldDescriptors['lastName'] = new DoctrineFieldDescriptor(
            'lastName',
            'lastName',
            self::$entityName
        );
        $this->fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::$titleEntityName,
            array(
                self::$titleEntityName => self::$entityName . '.title',
            )
        );
        $this->fieldDescriptors['company'] = new DoctrineFieldDescriptor(
            'name',
            'company',
            self::$accountEntityName,
            array(
                self::$accountContactEntityName => self::$entityName . '.accountContacts',
                self::$accountEntityName => self::$accountContactEntityName . '.account'
            )
        );
        $this->fieldDescriptors['city'] = new DoctrineFieldDescriptor(
            'city',
            'city',
            self::$addressEntityName,
            array(
                self::$contactAddressEntityName => self::$entityName . '.contactAddresses',
                self::$addressEntityName => self::$contactAddressEntityName . '.address',
            )
        );
    }

    /**
     * returns all fields that can be used by list
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldsAction()
    {
        // default contacts list
        return $this->handleView($this->view(array_values($this->fieldDescriptors), 200));
    }

    /**
     * lists all contacts
     * optional parameter 'flat' calls listAction
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {

        if(!is_null($request->get('bySystem')) && $request->get('bySystem') == true){
            $contacts = $this->getContactsByUserSystem();
            $view = $this->view($this->createHalResponse($contacts), 200);
            return $this->handleView($view);
        }

        // flat structure
        if ($request->get('flat') == 'true') {

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->getRestHelper();

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->fieldDescriptors);

            // TODO: main address
            // TODO: fullname

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_contacts',
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
     * Deletes a Contact with the given ID from database
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            /** @var Contact $contact */
            $contact = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->findByIdAndDelete($id);

            if (!$contact) {
                throw new EntityNotFoundException(self::$entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();

            $addresses = $contact->getAddresses();
            /** @var Address $address */
            foreach ($addresses as $address) {
                if (!$address->hasRelations()) {
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
                    ->getRepository(self::$entityName)
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
            if ($parentData != null &&
                $parentData['id'] != null &&
                $parentData['id'] != 'null' &&
                $parentData['id'] != ''
            ) {
                /** @var Account $parent */
                $parent = $this->getDoctrine()
                    ->getRepository(self::$accountEntityName)
                    ->findAccountById($parentData['id']);

                if (!$parent) {
                    throw new EntityNotFoundException(self::$accountEntityName, $parentData['id']);
                }
                // create new account-contact relation
                $this->createMainAccountContact($contact, $parent, $request->get('position'));
            }
            $birthday = $request->get('birthday');
            if (!empty($birthday)) {
                $contact->setBirthday(new DateTime($birthday));
            }

            $contact->setCreated(new DateTime());
            $contact->setChanged(new DateTime());

            $contact->setFormOfAddress($formOfAddress['id']);

            $contact->setDisabled($disabled);

            $salutation = $request->get('salutation');
            if (!empty($salutation)) {
                $contact->setSalutation($salutation);
            }

            // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
            $this->addNewContactRelations($contact, $request);

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
     * @param $position
     * @return bool|AccountContact
     */
    private function getMainAccountContactOrCreateNew(Contact $contact, Account $account, $position)
    {
        $accountContact = $this->getMainAccountContact($contact);
        if (!$accountContact) {
            $accountContact = $this->createMainAccountContact($contact, $account, $position);
        } else {
            $accountContact->setPosition($position);
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
            /** @var AccountContact $accountContact */
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
     * @param $position
     * @return AccountContact
     */
    private function createMainAccountContact(Contact $contact, Account $account, $position)
    {
        $accountContact = new AccountContact();
        $accountContact->setAccount($account);
        $accountContact->setContact($contact);
        $accountContact->setMain(true);
        $this->getDoctrine()->getManager()->persist($accountContact);
        $contact->addAccountContact($accountContact);
        $accountContact->setPosition($position);
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
                if ($parentData != null &&
                    $parentData['id'] != null &&
                    $parentData['id'] != 'null' &&
                    $parentData['id'] != ''
                ) {
                    /** @var Account $parent */
                    $parent = $this->getDoctrine()
                        ->getRepository(self::$accountEntityName)
                        ->findAccountById($parentData['id']);

                    if (!$parent) {
                        throw new EntityNotFoundException(self::$accountEntityName, $parentData['id']);
                    }
                    $accountContact = $this->getMainAccountContactOrCreateNew(
                        $contact,
                        $parent,
                        $request->get('position')
                    );

                    if ($accountContact) {
                        $accountContact->setAccount($parent);
                    }
                } else {
                    if ($accountContact = $this->getMainAccountContact($contact)) {
                        $em->remove($accountContact);
                    }
                }

                // process details
                if (!($this->processEmails($contact, $request->get('emails', array()))
                    && $this->processPhones($contact, $request->get('phones'), array())
                    && $this->processAddresses($contact, $request->get('addresses'), array())
                    && $this->processNotes($contact, $request->get('notes', array()))
                    && $this->processFaxes($contact, $request->get('faxes', array()))
                    && $this->processTags($contact, $request->get('tags', array()))
                    && $this->processUrls($contact, $request->get('urls', array())))
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
     * @return AbstractContactManager
     */
    protected function getContactManager()
    {
        return $this->get('sulu_contact.contact_manager');
    }

    /**
     * Returns a list of contacts which have a user in the sulu system
     */
    protected function getContactsByUserSystem(){
        $repo = $this->get('sulu_security.user_repository');
        $users = $repo->getUserInSystem();
        $contacts = [];

        foreach($users  as $user){
            $contacts[] = $user->getContact();
        }

        return $contacts;
    }
}
