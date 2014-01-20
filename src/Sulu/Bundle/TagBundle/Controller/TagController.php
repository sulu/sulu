<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Controller;

use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Entity\TagRepository;
use Sulu\Bundle\TagBundle\Event\TagEvents;
use Sulu\Bundle\TagBundle\Event\TagMergeEvent;
use Sulu\Bundle\TagBundle\Tag\Exception\TagNotFoundException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use FOS\RestBundle\Controller\Annotations\Post;

/**
 * Makes tag available through
 * @package Sulu\Bundle\TagBundle\Controller
 */
class TagController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluTagBundle:Tag';

    protected $unsortable = array();

    /**
     * Returns a single tag with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function ($id) {
                return $this->get('sulu_tag.tag_manager')->loadById($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns all tags
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        if ($this->getRequest()->get('flat') == 'true') {
            // flat structure
            $view = $this->responseList();
        } else {
            $tags = $this->get('sulu_tag.tag_manager')->loadAll();
            $view = $this->view($this->createHalResponse($tags), 200);
        }

        return $this->handleView($view);
    }

    /**
     * Inserts a new tag
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function postAction()
    {
        $name = $this->getRequest()->get('name');

        try {
            $em = $this->getDoctrine()->getManager();

            $tag = new Tag();
            $tag->setName($name);

            $tag->setCreated(new \DateTime());
            $tag->setChanged(new \DateTime());

            $em->persist($tag);
            $em->flush();

            $view = $this->view($tag, 200);
        } catch (DBALException $dbale) {
            if ($dbale->getPrevious()->getCode() === '23000') { // Check if unique constraint fails
                $re = new RestException('The tag with the name "' . $name . '" already exists.');
                $view = $this->view($re->toArray(), 400);
            } else {
                throw $dbale;
            }
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the tag with the given ID
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function putAction($id)
    {
        $name = $this->getRequest()->get('name');

        try {
            if ($name == null) {
                throw new MissingArgumentException($this->entityName, 'name');
            }

            /** @var Tag $tag */
            $tag = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findTagById($id);

            if (!$tag) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $tag->setName($name);
                $tag->setChanged(new \DateTime());

                $em->flush();
                $view = $this->view($tag, 200);
            }
        } catch (DBALException $dbale) {
            if ($dbale->getPrevious()->getCode() === '23000') { // Check if unique constraint fails
                $re = new RestException('The tag with the name "' . $name . '" already exists.');
                $view = $this->view($re->toArray(), 400);
            } else {
                throw $dbale;
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the tag with the given ID
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            try {
                $this->get('sulu_tag.tag_manager')->delete($id);
            } catch (TagNotFoundException $tnfe) {
                throw new EntityNotFoundException($this->entityName, $id);
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * POST Route annotation.
     * @Post("/tags/merge")
     */
    public function postMergeAction()
    {
        try {
            /** @var TagRepository $tagRepository */
            $tagRepository = $this->getDoctrine()->getRepository($this->entityName);
            $em = $this->getDoctrine()->getManager();

            $srcTagId = $this->getRequest()->get('src');
            $destTagId = $this->getRequest()->get('dest');

            $srcTag = $tagRepository->findTagById($srcTagId);
            $destTag = $tagRepository->findTagById($destTagId);

            if (!$srcTag) {
                throw new EntityNotFoundException($this->entityName, $srcTagId);
            }

            if (!$destTag) {
                throw new EntityNotFoundException($this->entityName, $destTagId);
            }

            $em->remove($srcTag);
            $em->flush();

            // throw an tag.merge event
            $event = new TagMergeEvent($srcTag, $destTag);
            $this->get('event_dispatcher')->dispatch(TagEvents::TAG_MERGE, $event);

            $view = $this->view(null, 303, array('location' => $destTag->getLinks()['self']));
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        }

        return $this->handleView($view);
    }
} 
