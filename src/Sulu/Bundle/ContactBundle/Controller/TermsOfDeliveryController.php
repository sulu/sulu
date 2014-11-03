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
use Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpFoundation\Request;
use Hateoas\Representation\CollectionRepresentation;

/**
 * Makes account categories available through a REST API
 * Used RouteResource annotation to prevent automatic parenting of rest controllers
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class TermsOfDeliveryController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluContactBundle:TermsOfDelivery';
    protected static $entityKey = 'termsOfDeliveries';

    /**
     * Shows a single terms of delivery
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofdeliveries/{id}")
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function ($id) {
                return $this->getDoctrine()
                    ->getRepository(self::$entityName)
                    ->find($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all terms of deliveries
     * optional parameter 'flat' calls listAction
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofdeliveries")
     */
    public function cgetAction()
    {
        $termsOfDelivery = $this->getDoctrine()->getRepository(self::$entityName)->findBy([], array('terms' => 'ASC'));
        $list = new CollectionRepresentation($termsOfDelivery, self::$entityKey);

        $view = $this->view($list, 200);
        return $this->handleView($view);
    }

    /**
     * Creates a terms of delivery
     * @return \Symfony\Component\HttpFoundation\Response
     * @param Request $request
     * @Route("/termsofdeliveries")
     */
    public function postAction(Request $request)
    {
        $terms = $request->get('terms');

        try {
            if ($terms == null) {
                throw new RestException('There is no term-name for the term-of-delivery given');
            }

            $em = $this->getDoctrine()->getManager();
            $termsOfDelivery = new TermsOfDelivery();
            $termsOfDelivery->setTerms($terms);

            $em->persist($termsOfDelivery);
            $em->flush();

            $view = $this->view($termsOfDelivery, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing terms-of-delivery with the given id
     * @param integer $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @Route("/termsofdeliveries/{id}")
     */
    public function putAction(Request $request, $id)
    {
        try {
            /** @var TermsOfDelivery $termsOfDelivery */
            $termsOfDelivery = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->find($id);

            if (!$termsOfDelivery) {
                throw new EntityNotFoundException(self::$entityName, $id);
            } else {
                $terms = $request->get('terms');

                if ($terms == null || $terms == '') {
                    throw new RestException('There is no category-name for the terms of delivery given');
                } else {
                    $em = $this->getDoctrine()->getManager();
                    $termsOfDelivery->setTerms($terms);

                    $em->flush();
                    $view = $this->view($termsOfDelivery, 200);
                }
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Delete terms-of-delivery with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofdeliveries/{id}")
     */
    public function deleteAction($id)
    {
        try {
            $delete = function ($id) {

                /* @var TermsOfDelivery $termsOfDelivery */
                $termsOfDelivery = $this->getDoctrine()
                    ->getRepository(self::$entityName)
                    ->find($id);

                if (!$termsOfDelivery) {
                    throw new EntityNotFoundException(self::$entityName, $id);
                }

                $em = $this->getDoctrine()->getManager();
                $em->remove($termsOfDelivery);
                $em->flush();
            };

            $view = $this->responseDelete($id, $delete);

        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Add or update a bunch of terms of delivery
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofdeliveries")
     */
    public function patchAction(Request $request)
    {
        try {
            $data = [];

            $i = 0;
            while ($item = $request->get($i)) {

                if (!isset($item['terms'])) {
                    throw new RestException('There is no category-name for the account-category given');
                }

                $data[] = $this->addAndUpdateCategories($item);
                $i++;
            }

            $this->getDoctrine()->getManager()->flush();
            $view = $this->view($data, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }
        return $this->handleView($view);
    }

    /**
     * Helper function for patch action
     * @param $item
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @return added or updated entity
     */
    private function addAndUpdateCategories($item)
    {
        if (isset($item['id']) && !empty($item['id'])) {
            /* @var TermsOfDelivery $termsOfDelivery */
            $termsOfDelivery = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->find($item['id']);

            if ($termsOfDelivery == null) {
                throw new EntityNotFoundException(self::$entityName, $item['id']);
            } else {
                $termsOfDelivery->setTerms($item['terms']);
            }

        } else {
            $termsOfDelivery = new TermsOfDelivery();
            $termsOfDelivery->setTerms($item['terms']);
            $this->getDoctrine()->getManager()->persist($termsOfDelivery);
        }

        return $termsOfDelivery;
    }
}
