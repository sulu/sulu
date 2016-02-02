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
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes positions available through a REST API
 * Used RouteResource annotation to prevent automatic parenting of rest controllers.
 */
class PositionController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluContactBundle:Position';

    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'positions';

    /**
     * Shows a single position for the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/positions/{id}")
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
     * lists all positions
     * optional parameter 'flat' calls listAction.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/positions")
     */
    public function cgetAction()
    {
        $list = new CollectionRepresentation(
            $this->getDoctrine()->getRepository(self::$entityName)->findBy([], ['position' => 'ASC']),
            self::$entityKey
        );

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new position.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/positions")
     */
    public function postAction(Request $request)
    {
        $name = $request->get('position');

        try {
            if ($name == null) {
                throw new RestException(
                    'There is no position-name for the given name'
                );
            }

            $em = $this->getDoctrine()->getManager();
            $position = new Position();
            $position->setPosition($name);

            $em->persist($position);
            $em->flush();

            $view = $this->view($position, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing position for the given id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int                                       $id      The id of the position to update
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            /** @var Position $position */
            $position = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->find($id);

            if (!$position) {
                throw new EntityNotFoundException(self::$entityName, $id);
            } else {
                $name = $request->get('position');

                if (empty($name)) {
                    throw new RestException('There is no position-name for the given name');
                } else {
                    $em = $this->getDoctrine()->getManager();
                    $position->setPosition($name);

                    $em->flush();
                    $view = $this->view($position, 200);
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
     * Delete a position for the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/positions/{id}")
     */
    public function deleteAction($id)
    {
        try {
            $delete = function ($id) {

                /* @var Position $position */
                $position = $this->getDoctrine()
                    ->getRepository(self::$entityName)
                    ->find($id);

                if (!$position) {
                    throw new EntityNotFoundException(self::$entityName, $id);
                }

                $em = $this->getDoctrine()->getManager();
                $em->remove($position);
                $em->flush();
            };

            $view = $this->responseDelete($id, $delete);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Add or update a bunch of positions.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("contact/positions")
     */
    public function patchAction(Request $request)
    {
        try {
            $data = [];

            $i = 0;
            while ($item = $request->get($i)) {
                if (!isset($item['position'])) {
                    throw new RestException(
                        'There is no position-name for the given name'
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
     * @return Position added or updated entity
     */
    private function addAndUpdateTitles($item)
    {
        if (isset($item['id']) && !empty($item['id'])) {
            /* @var Position $position */
            $position = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->find($item['id']);

            if ($position == null) {
                throw new EntityNotFoundException(self::$entityName, $item['id']);
            } else {
                $position->setPosition($item['position']);
            }
        } else {
            $position = new Position();
            $position->setPosition($item['position']);
            $this->getDoctrine()->getManager()->persist($position);
        }

        return $position;
    }
}
