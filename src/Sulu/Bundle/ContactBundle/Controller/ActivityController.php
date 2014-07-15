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

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use \DateTime;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Component\Rest\RestHelperInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Makes activities available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class ActivityController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluContactBundle:Activity';
    protected static $activityStatusEntityName = 'SuluContactBundle:ActivityStatus';
    protected static $activityTypeEntityName = 'SuluContactBundle:ActivityType';
    protected static $activityPriorityEntityName = 'SuluContactBundle:ActivityPriority';
    protected static $contactEntityName = 'SuluContactBundle:Contact';
    protected static $accountEntityName = 'SuluContactBundle:Account';

    /**
     * @var string
     */
    protected $basePath = 'admin/api/activities';
    protected $bundlePrefix = 'contact.activities.';

    /**
     * TODO: move the field descriptors to a manager
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    /**
     * TODO: move field descriptors to a manager
     */
    public function __construct()
    {
        $this->fieldDescriptors = array();
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName,
            array(),
            false,
            '',
            '',
            'public.id'
        );
        $this->fieldDescriptors['subject'] = new DoctrineFieldDescriptor('subject', 'subject', self::$entityName);
        $this->fieldDescriptors['note'] = new DoctrineFieldDescriptor('note', 'note', self::$entityName);
        $this->fieldDescriptors['dueDate'] = new DoctrineFieldDescriptor('dueDate', 'dueDate', self::$entityName);
        $this->fieldDescriptors['startDate'] = new DoctrineFieldDescriptor('startDate', 'startDate', self::$entityName);
        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor('created', 'created', self::$entityName);
        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor('changed', 'changed', self::$entityName);

        $this->fieldDescriptors['activityStatus'] = new DoctrineFieldDescriptor(
            'name', 'activityStatus', self::$activityStatusEntityName,
            array(
                self::$activityStatusEntityName => self::$entityName . '.activityStatus'
            )
        );

        $this->fieldDescriptors['activityPriority'] = new DoctrineFieldDescriptor(
            'name', 'activityPriority', self::$activityPriorityEntityName,
            array(
                self::$activityPriorityEntityName => self::$entityName . '.activityPriority'
            )
        );

        $this->fieldDescriptors['activityType'] = new DoctrineFieldDescriptor(
            'name', 'activityType', self::$activityTypeEntityName,
            array(
                self::$activityTypeEntityName => self::$entityName . '.activityType'
            )
        );

        // TODO should be excluded
        $this->fieldDescriptors['account'] = new DoctrineFieldDescriptor(
            'id', 'account', self::$accountEntityName,
            array(
                self::$accountEntityName => self::$entityName . '.account'
            ),
            true
        );

        // TODO should be excluded
        $this->fieldDescriptors['contact'] = new DoctrineFieldDescriptor(
            'id', 'contact', self::$contactEntityName . 'contact',
            array(
                self::$contactEntityName . 'contact' => self::$entityName . '.contact'
            ),
            true
        );

        // TODO use fullName when implemented
        $this->fieldDescriptors['assignedContact'] = new DoctrineFieldDescriptor(
            'lastName', 'assignedContact', self::$contactEntityName . 'assignedContact',
            array(
                self::$contactEntityName . 'assignedContact' => self::$entityName . '.assignedContact'
            )
        );
    }

    /**
     * returns all fields that can be used by list
     * @return mixed
     */
    public function fieldsAction()
    {
        return $this->handleView($this->view(array_values($this->fieldDescriptors), 200));
    }

    /**
     * Shows a single activity with the given id
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
                    ->findActivitiesById($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all activities
     * optional parameter 'flat' calls listAction
     * optional parameter 'contact' calls listAction for all activities for a contact (in combination with flat)
     * optional parameter 'account' calls listAction for all activities for a account (in combination with flat)
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $filter = array();

        $type = $request->get('type');
        if ($type) {
            $filter['type'] = $type;
        }

        $account = $request->get('account');
        if ($account) {
            $filter['account'] = $account;
        }

        $contact = $request->get('contact');
        if ($contact) {
            $filter['contact'] = $contact;
        }

        if ($request->get('flat') == 'true') {

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->fieldDescriptors);

            foreach ($filter as $key => $value) {
                $listBuilder->where($this->fieldDescriptors[$key], $value);
            }

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_activities',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );

        } else {
            $activities = $this->getDoctrine()->getRepository(self::$entityName)->findAllActivities();
            $list = new CollectionRepresentation($activities, self::$entityKey);
        }

        $view = $this->view($list, 200);
        return $this->handleView($view);
    }

    /**
     * Updates an activity with a given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $activity = $this->getEntityById(self::$entityName, $id);

            $this->processActivityData($activity, $request);

            $em->persist($activity);
            $em->flush();

            $view = $this->view($activity, 200);

        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }
        return $this->handleView($view);
    }

    /**
     * Deletes an activity with a given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $em = $this->getDoctrine()->getManager();
            $activity = $this->getEntityById(self::$entityName, $id);
            $em->remove($activity);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);
        return $this->handleView($view);
    }

    /**
     * Creates a new activity
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $activity = new Activity();

            $this->processActivityData($activity, $request);

            $activity->setCreator($this->getUser());
            $activity->setCreated(new \DateTime());

            $em->persist($activity);
            $em->flush();

            $view = $this->view($activity, 200);

        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }
        return $this->handleView($view);
    }

    /**
     * Processes the data for an activity from an request
     * @param Activity $activity
     * @param Request $request
     * @throws RestException
     */
    protected function processActivityData(Activity $activity, Request $request)
    {
        $subject = $request->get('subject');
        $dueDate = $request->get('dueDate');
        $assignedContactData = $request->get('assignedContact');

        if ($subject == null || $dueDate == null || $assignedContactData == null) {
            throw new RestException('There is no name or dueDate or assignedContact for the activity given');
        }

        // required data
        $activity->setSubject($subject);
        $activity->setDueDate(new DateTime($dueDate));

        if (!is_null($assignedContactData['id'])) {
            /* @var Contact $assignedContact */
            $assignedContact = $this->getEntityById(self::$contactEntityName, $assignedContactData['id']);
            $activity->setAssignedContact($assignedContact);
        }

        // changer and changed
        $activity->setChanged(new \DateTime());
        $activity->setChanger($this->getUser());

        $note = $request->get('note');
        $status = $request->get('activityStatus');
        $priority = $request->get('activityPriority');
        $type = $request->get('activityType');
        $startDate = $request->get('startDate');
        $belongsToAccount = $request->get('account');
        $belongsToContact = $request->get('contact');

        if (!is_null($note)) {
            $activity->setNote($note);
        }
        if (!is_null($status)) {
            /* @var ActivityStatus $activityStatus */
            $activityStatus = $this->getEntityById(self::$activityStatusEntityName, $status['id']);
            $activity->setActivityStatus($activityStatus);
        }
        if (!is_null($priority)) {
            /* @var ActivityPriority $activityPriority */
            $activityPriority = $this->getEntityById(self::$activityPriorityEntityName, $priority['id']);
            $activity->setActivityPriority($activityPriority);
        }
        if (!is_null($type)) {
            /* @var ActivityType $activityType */
            $activityType = $this->getEntityById(self::$activityTypeEntityName, $type['id']);
            $activity->setActivityType($activityType);
        }
        if (!is_null($startDate)) {
            $activity->setStartDate(new \DateTime($startDate));
        }
        if (!is_null($belongsToAccount)) {
            /* @var Account $account */
            $account = $this->getEntityById(self::$accountEntityName, $belongsToAccount['id']);
            $activity->setAccount($account);
            $activity->setContact(null);
        } else {
            if (!is_null($belongsToContact)) {
                /* @var Contact $contact */
                $contact = $this->getEntityById(self::$contactEntityName, $belongsToContact['id']);
                $activity->setContact($contact);
                $activity->setAccount(null);
            } else {
                throw new RestException('No account or contact set!', self::$entityName);
            }
        }
    }

    /**
     * Returns an entity for a specific id
     * @param $entityName
     * @param $id
     * @return mixed
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function getEntityById($entityName, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($entityName)->find($id);
        if (!$entity) {
            throw new EntityNotFoundException($entityName, $id);
        }
        return $entity;
    }

}
