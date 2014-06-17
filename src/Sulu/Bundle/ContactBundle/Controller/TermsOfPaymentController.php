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
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Route;

/**
 * Makes account categories available through a REST API
 * Used RouteResource annotation to prevent automatic parenting of rest controllers
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class TermsOfPaymentController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluContactBundle:TermsOfPayment';

    /**
     * Shows a single terms of payment
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofpayment/{id}")
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
     * lists all terms of payment
     * optional parameter 'flat' calls listAction
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofpayments")
     */
    public function cgetAction()
    {
        $termsOfPayment = $this->getDoctrine()->getRepository($this->entityName)->findAll();
        $view = $this->view($this->createHalResponse($termsOfPayment), 200);

        return $this->handleView($view);
    }

    /**
     * Creates a terms of payment
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofpayment")
     */
    public function postAction()
    {
        $terms = $this->getRequest()->get('terms');

        try {
            if ($terms == null) {
                throw new RestException('There is no term-name for the term-of-payment given');
            }

            $em = $this->getDoctrine()->getManager();
            $termsOfPayment = new TermsOfPayment();
            $termsOfPayment->setTerms($terms);

            $em->persist($termsOfPayment);
            $em->flush();

            $view = $this->view($termsOfPayment, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing terms-of-payment with the given id
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @Route("/termsofpayment/{id}")
     */
    public function putAction($id)
    {
        try {
            /** @var TermsOfPayment $termsOfPayment */
            $termsOfPayment = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($id);

            if (!$termsOfPayment) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {
                $terms = $this->getRequest()->get('terms');

                if ($terms == null || $terms == '') {
                    throw new RestException('There is no category-name for the account-category given');
                } else {
                    $em = $this->getDoctrine()->getManager();
                    $termsOfPayment->setTerms($terms);

                    $em->flush();
                    $view = $this->view($termsOfPayment, 200);
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
     * Delete terms-of-payment with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofpayment/{id}")
     */
    public function deleteAction($id)
    {
        try {
            $delete = function ($id) {

                /* @var TermsOfPayment $termsOfPayment */
                $termsOfPayment = $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->find($id);

                if (!$termsOfPayment) {
                    throw new EntityNotFoundException($this->entityName, $id);
                }

                $em = $this->getDoctrine()->getManager();
                $em->remove($termsOfPayment);
                $em->flush();
            };

            $view = $this->responseDelete($id, $delete);

        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Add or update a bunch of terms of payment
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/termsofpayments")
     */
    public function patchAction()
    {
        try {
            $data = [];

            /** @var Request $request */
            $request = $this->getRequest();
            $i = 0;
            while ($item = $request->get($i)) {

                if (!isset($item['terms'])) {
                    throw new RestException('There is no term-name for the terms-of-payment given');
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
            /* @var TermsOfPayment $termsOfPayment */
            $termsOfPayment = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($item['id']);

            if ($termsOfPayment == null) {
                throw new EntityNotFoundException($this->entityName, $item['id']);
            } else {
                $termsOfPayment->setTerms($item['terms']);
            }

        } else {
            $termsOfPayment = new TermsOfPayment();
            $termsOfPayment->setTerms($item['terms']);
            $this->getDoctrine()->getManager()->persist($termsOfPayment);
        }

        return $termsOfPayment;
    }
}
