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

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes account categories available through a REST API
 * Used RouteResource annotation to prevent automatic parenting of rest controllers.
 */
class ContactTitleController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluContactBundle:ContactTitle';

    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'contactTitles';

    /**
     * Shows a single contact title for the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/titles/{id}")
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
     * lists all contact titles
     * optional parameter 'flat' calls listAction.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/titles")
     */
    public function cgetAction()
    {
        $list = new CollectionRepresentation(
            $this->getDoctrine()->getRepository(self::$entityName)->findBy([], ['title' => 'ASC']),
            self::$entityKey
        );

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new contact title.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/titles")
     */
    public function postAction(Request $request)
    {
        $name = $request->get('title');

        try {
            if ($name == null) {
                throw new RestException(
                    'There is no title-name for the given title'
                );
            }

            $em = $this->getDoctrine()->getManager();
            $title = new ContactTitle();
            $title->setTitle($name);

            $em->persist($title);
            $em->flush();

            $view = $this->view($title, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing contact title for the given id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int                                       $id      The id of the title to update
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            /** @var ContactTitle $title */
            $title = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->find($id);

            if (!$title) {
                throw new EntityNotFoundException(self::$entityName, $id);
            } else {
                $name = $request->get('title');

                if (empty($name)) {
                    throw new RestException('There is no title-name for the given title');
                } else {
                    $em = $this->getDoctrine()->getManager();
                    $title->setTitle($name);

                    $em->flush();
                    $view = $this->view($title, 200);
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
     * Delete a contact title for the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/titles/{id}")
     */
    public function deleteAction($id)
    {
        try {
            $delete = function ($id) {
                /* @var ContactTitle $title */
                $title = $this->getDoctrine()
                    ->getRepository(self::$entityName)
                    ->find($id);

                if (!$title) {
                    throw new EntityNotFoundException(self::$entityName, $id);
                }

                $em = $this->getDoctrine()->getManager();
                $em->remove($title);
                $em->flush();
            };

            $view = $this->responseDelete($id, $delete);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Add or update a bunch of contact titles.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/titles")
     */
    public function patchAction(Request $request)
    {
        try {
            $data = [];

            $i = 0;
            while ($item = $request->get($i)) {
                if (!isset($item['title'])) {
                    throw new RestException(
                        'There is no title-name for the given title'
                    );
                }

                $data[] = $this->addAndUpdateTitles($item);
                ++$i;
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
     * Helper function for patch action.
     *
     * @param $item
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return ContactTitle added or updated entity
     */
    private function addAndUpdateTitles($item)
    {
        if (isset($item['id']) && !empty($item['id'])) {
            /* @var ContactTitle $title */
            $title = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->find($item['id']);

            if ($title == null) {
                throw new EntityNotFoundException(self::$entityName, $item['id']);
            } else {
                $title->setTitle($item['title']);
            }
        } else {
            $title = new ContactTitle();
            $title->setTitle($item['title']);
            $this->getDoctrine()->getManager()->persist($title);
        }

        return $title;
    }
}
