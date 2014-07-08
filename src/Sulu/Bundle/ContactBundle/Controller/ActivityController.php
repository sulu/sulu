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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;

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
 * Makes activities available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class ActivityController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluContactBundle:Activity';
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
     * @Get("accounts/fields")
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
     * Creates a new activity
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {


        try {


            $em = $this->getDoctrine()->getManager();
            $activity = new Activity();

            $this->processActivityData($activity, $request, $em);

            $em->persist($activity);
            $em->flush();

            $view = $this->view($activity, 200);

        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Processes the data for an activity from an request
     * @param Activity $activity
     * @param Request $request
     * @param EntityManager $em
     * @throws RestException
     */
    protected function processActivityData(Activity $activity, Request $request, EntityManager $em){

        $subject = $request->get('subject');
        $dueDate = $request->get('dueDate');
        $assignedContact = $request->get('assignedContact');

        if ($subject == null || $dueDate == null || $assignedContact == null) {
            throw new RestException('There is no name or dueDate or assignedContact for the activity given');
        }

        $activity->setSubject($subject);
        $activity->setDueDate(new DateTime($dueDate));
// TODO
//        $em->getRepository($this->contactEntityName)->find()
//        $activity->setAssignedContact()

    }

}
