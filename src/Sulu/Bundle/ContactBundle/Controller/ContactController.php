<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Configuration\Exclusion;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Util\IndexComparatorInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes contacts available through a REST API.
 */
class ContactController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'contacts';
    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected static $positionEntityName = 'SuluContactBundle:Position';

    // serialization groups for contact
    protected static $contactSerializationGroups = [
        'fullContact',
        'partialAccount',
        'partialTag',
        'partialMedia',
        'partialCategory',
    ];

    /**
     * @var string
     */
    protected $basePath = 'admin/api/contacts';

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.contacts.';

    // TODO: move the field descriptors to a manager
    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    protected $accountContactFieldDescriptors;

    /**
     * @return RestHelperInterface
     */
    protected function getRestHelper()
    {
        return $this->get('sulu_core.doctrine_rest_helper');
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
            $this->initFieldDescriptors();
        }

        return $this->accountContactFieldDescriptors;
    }

    private function initFieldDescriptors()
    {
        $this->fieldDescriptors = $this->get(
            'sulu_core.list_builder.field_descriptor_factory'
        )->getFieldDescriptorForClass(Contact::class);

        // field descriptors for the account contact list
        $this->accountContactFieldDescriptors = [];
        $this->accountContactFieldDescriptors['id'] = $this->fieldDescriptors['id'];
        $this->accountContactFieldDescriptors['fullName'] = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor(
                    'firstName',
                    'firstName',
                    $this->container->getParameter('sulu.model.contact.class')
                ),
                new DoctrineFieldDescriptor(
                    'lastName',
                    'lastName',
                    $this->container->getParameter('sulu.model.contact.class')
                ),
            ],
            'fullName',
            'public.name',
            ' ',
            false,
            true,
            'string',
            '',
            '',
            false
        );
        $this->accountContactFieldDescriptors['position'] = new DoctrineFieldDescriptor(
            'position',
            'position',
            self::$positionEntityName,
            'contact.contacts.position',
            [
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    $this->container->getParameter('sulu.model.contact.class') . '.accountContacts'
                ),
                self::$positionEntityName => new DoctrineJoinDescriptor(
                    self::$positionEntityName,
                    self::$accountContactEntityName . '.position'
                ),
            ],
            false,
            true,
            'string',
            '',
            '',
            false
        );

        // FIXME use field descriptor with expression when implemented
        $this->accountContactFieldDescriptors['isMainContact'] = new DoctrineFieldDescriptor(
            'main',
            'isMainContact',
            self::$accountContactEntityName,
            'contact.contacts.main-contact',
            [
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    $this->container->getParameter('sulu.model.contact.class') . '.accountContacts'
                ),
            ],
            false,
            true,
            'radio',
            '',
            '',
            false
        );
    }

    /**
     * returns all fields that can be used by list.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldsAction(Request $request)
    {
        if ((bool) $request->get('accountContacts')) {
            return $this->handleView($this->view(array_values($this->getAccountContactFieldDescriptors()), 200));
        }

        // default contacts list
        return $this->handleView($this->view(array_values($this->getFieldDescriptors()), 200));
    }

    /**
     * lists all contacts
     * optional parameter 'flat' calls listAction.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $serializationGroups = [];
        $locale = $this->getLocale($request);

        if ($request->get('flat') == 'true') {
            $list = $this->getList($request, $locale);
        } else {
            if ($request->get('bySystem') == true) {
                $contacts = $this->getContactsByUserSystem();
                $serializationGroups[] = 'select';
            } else {
                $contacts = $this->getDoctrine()->getRepository(
                    $this->container->getParameter('sulu.model.contact.class')
                )->findAll();
                $serializationGroups = array_merge(
                    $serializationGroups,
                    static::$contactSerializationGroups
                );
            }
            // convert to api-contacts
            $apiContacts = [];
            foreach ($contacts as $contact) {
                $apiContacts[] = $this->getContactManager()->getContact($contact, $locale);
            }

            $exclusion = null;
            if (count($serializationGroups) > 0) {
                $exclusion = new Exclusion($serializationGroups);
            }

            $list = new CollectionRepresentation($apiContacts, self::$entityKey, null, $exclusion, $exclusion);
        }

        $view = $this->view($list, 200);

        // set serialization groups
        if (count($serializationGroups) > 0) {
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    $serializationGroups
                )
            );
        }

        return $this->handleView($view);
    }

    /**
     * Returns list for cget.
     *
     * @param Request $request
     * @param string $locale
     *
     * @return ListRepresentation
     */
    private function getList(Request $request, $locale)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->getRestHelper();

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $listBuilder = $factory->create($this->container->getParameter('sulu.model.contact.class'));
        $restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

        $listResponse = $this->prepareListResponse($request, $listBuilder, $locale);

        return new ListRepresentation(
            $listResponse,
            self::$entityKey,
            'get_contacts',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );
    }

    /**
     * Prepare list response.
     *
     * @param Request $request
     * @param DoctrineListBuilder $listBuilder
     * @param string $locale
     *
     * @return array
     */
    private function prepareListResponse(Request $request, DoctrineListBuilder $listBuilder, $locale)
    {
        $idsParameter = $request->get('ids');
        $ids = array_filter(explode(',', $idsParameter));
        if ($idsParameter !== null && count($ids) === 0) {
            return [];
        }

        if ($idsParameter !== null) {
            $listBuilder->in($this->fieldDescriptors['id'], $ids);
        }

        $listResponse = $listBuilder->execute();
        $listResponse = $this->addAvatars($listResponse, $locale);

        if ($idsParameter !== null) {
            $comparator = $this->getComparator();
            // the @ is necessary in case of a PHP bug https://bugs.php.net/bug.php?id=50688
            @usort(
                $listResponse,
                function ($a, $b) use ($comparator, $ids) {
                    return $comparator->compare($a['id'], $b['id'], $ids);
                }
            );
        }

        return $listResponse;
    }

    /**
     * Deletes a Contact with the given ID from database.
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        try {
            $deleteCallback = $this->getContactManager()->delete();
            $view = $this->responseDelete($id, $deleteCallback);
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Shows the contact with the given Id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $contactManager = $this->getContactManager();
        $locale = $this->getUser()->getLocale();

        try {
            $view = $this->responseGetById(
                $id,
                function ($id) use ($contactManager, $locale) {
                    return $contactManager->getById($id, $locale);
                }
            );

            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    static::$contactSerializationGroups
                )
            );
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new contact.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $this->checkArguments($request);
            $contact = $this->getContactManager()->save(
                $request->request->all()
            );
            $apiContact = $this->getContactManager()->getContact(
                $contact,
                $this->getLocale($request)
            );
            $view = $this->view($apiContact, 200);
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    static::$contactSerializationGroups
                )
            );
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (MissingArgumentException $maex) {
            $view = $this->view($maex->toArray(), 400);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id, Request $request)
    {
        try {
            $contact = $this->getContactManager()->save(
                $request->request->all(),
                $id
            );

            $apiContact = $this->getContactManager()->getContact($contact, $this->getUser()->getLocale());
            $view = $this->view($apiContact, 200);
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    static::$contactSerializationGroups
                )
            );
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Partially update an existing contact.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction($id, Request $request)
    {
        try {
            $contact = $this->getContactManager()->save(
                $request->request->all(),
                $id,
                true
            );

            $apiContact = $this->getContactManager()->getContact($contact, $this->getUser()->getLocale());
            $view = $this->view($apiContact, 200);
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    static::$contactSerializationGroups
                )
            );
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @return ContactManager
     */
    protected function getContactManager()
    {
        return $this->get('sulu_contact.contact_manager');
    }

    /**
     * Returns a list of contacts which have a user in the sulu system.
     */
    protected function getContactsByUserSystem()
    {
        $repo = $this->get('sulu_security.user_repository');
        $users = $repo->findUserBySystem($this->getParameter('sulu_security.system'));
        $contacts = [];

        foreach ($users as $user) {
            $contacts[] = $user->getContact();
        }

        return $contacts;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.contact.people';
    }

    /**
     * Takes an array of contacts and resets the avatar containing the media id with
     * the actual urls to the avatars thumbnail.
     *
     * @param array $contacts
     * @param string $locale
     *
     * @return array
     */
    private function addAvatars($contacts, $locale)
    {
        $ids = array_filter(array_column($contacts, 'avatar'));
        $avatars = $this->get('sulu_media.media_manager')->getFormatUrls($ids, $locale);
        foreach ($contacts as $key => $contact) {
            if (array_key_exists('avatar', $contact)
                && $contact['avatar']
                && array_key_exists($contact['avatar'], $avatars)
            ) {
                $contacts[$key]['avatar'] = $avatars[$contact['avatar']];
            }
        }

        return $contacts;
    }

    // TODO: Use schema validation see:
    // https://github.com/sulu-io/sulu/issues/1136

    private function checkArguments(Request $request)
    {
        if ($request->get('firstName') == null) {
            throw new MissingArgumentException($this->container->getParameter('sulu.model.contact.class'), 'username');
        }
        if ($request->get('lastName') === null) {
            throw new MissingArgumentException($this->container->getParameter('sulu.model.contact.class'), 'password');
        }
        if ($request->get('formOfAddress') == null) {
            throw new MissingArgumentException($this->container->getParameter('sulu.model.contact.class'), 'contact');
        }
    }

    /**
     * @return IndexComparatorInterface
     */
    private function getComparator()
    {
        return $this->get('sulu_contact.util.index_comparator');
    }
}
