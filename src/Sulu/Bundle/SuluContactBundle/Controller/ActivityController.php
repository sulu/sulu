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

/**
 * Makes activities available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class ActivityController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluContactBundle:Activity';
    protected $entityStatusName = 'SuluContactBundle:ActivityStatus';
    protected $entityPriorityName = 'SuluContactBundle:ActivityPriority';
    protected $entityTypeName = 'SuluContactBundle:ActivityType';
    protected $contactEntityName = 'SuluContactBundle:Contact';
    protected $accountEntityName = 'SuluContactBundle:Account';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsDefault = array('subject', 'status', 'dueDate');

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array('startDate', 'account', 'contact');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array(
        'id',
        'created'
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(
        0 => 'subject',
        1 => 'status',
        2 => 'dueDate'
    );

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array(
        'id' => 'public.id',
        'status' => 'public.status'
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
    protected $fieldsWidth = array();

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.accounts.';

    /**
     * returns all fields that can be used by list
     * @Get("activities/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
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
                    ->getRepository($this->entityName)
                    ->find($id);
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
        $where = array();
        $type = $request->get('type');
        $account = $request->get('account');
        $contact = $request->get('contact');

        if ($type) {
            $where['type'] = $type;
        }
        if ($request->get('flat') == 'true') {

            if(!!$contact) {
                $where['contact_id'] = $contact;
            } else if (!!$account){
                $where['account_id'] = $account;
            }

            // TODO

            $view = $this->responseList($where);
        } else {
            $activities = $this->getDoctrine()->getRepository($this->entityName)->findAllActivities();
            $view = $this->view($this->createHalResponse($activities), 200);
        }
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
            $activity = $this->getEntityById($this->entityName, $id);

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
            $activity = $this->getEntityById($this->entityName, $id);
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
            $assignedContact = $this->getEntityById($this->contactEntityName, $assignedContactData['id']);
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
            $activityStatus = $this->getEntityById($this->entityStatusName, $status['id']);
            $activity->setActivityStatus($activityStatus);
        }
        if (!is_null($priority)) {
            /* @var ActivityPriority $activityPriority */
            $activityPriority = $this->getEntityById($this->entityPriorityName, $priority['id']);
            $activity->setActivityPriority($activityPriority);
        }
        if (!is_null($type)) {
            /* @var ActivityType $activityType */
            $activityType = $this->getEntityById($this->entityTypeName, $type['id']);
            $activity->setActivityType($activityType);
        }
        if (!is_null($startDate)) {
            $activity->setStartDate(new \DateTime($startDate));
        }
        if (!is_null($belongsToAccount)) {
            /* @var Account $account */
            $account = $this->getEntityById($this->accountEntityName, $belongsToAccount['id']);
            $activity->setAccount($account);
            $activity->setContact(null);
        } else if (!is_null($belongsToContact)) {
            /* @var Contact $contact */
            $contact = $this->getEntityById($this->contactEntityName, $belongsToContact['id']);
            $activity->setContact($contact);
            $activity->setAccount(null);
        } else {
            throw new RestException('No account or contact set!', $this->entityName);
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
