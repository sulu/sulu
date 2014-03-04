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

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagNotFoundException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;

use Sulu\Bundle\TagBundle\Controller\Exception\ConstraintViolationException;

/**
 * Makes tag available through
 * @package Sulu\Bundle\TagBundle\Controller
 */
class TagController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluTagBundle:Tag';

    protected $unsortable = array();

    protected $fieldsDefault = array(
        'name'
    );

    protected $fieldsValidation = array(
        'name' => array(
            'required' => true
        )
    );

    protected $fieldsEditable = array(
        'name'
    );

    protected $fieldsExcluded = array();

    protected $fieldsHidden = array(
        'created',
        'id',
        'creator_contact_lastName',
        'changed'
    );
    protected $fieldsRelations = array(
        'creator_contact_lastName'
    );
    protected $fieldsSortOrder = array(
        '0' => 'name',
        '1' => 'creator_contact_lastName',
        '2' => 'changed'
    );

    protected $fieldsTranslationKeys = array(
        'name' => 'tags.name',
        'creator_contact_lastName' => 'tags.author'
    );

    protected $bundlePrefix = 'tags.';

    /**
     * returns all fields that can be used by list
     * @Get("tags/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
    }

    /**
     * persists a setting
     * @Put("tags/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }


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
                return $this->get('sulu_tag.tag_manager')->findById($id);
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
            $tags = $this->get('sulu_tag.tag_manager')->findAll();

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
            if ($name == null) {
                throw new MissingArgumentException($this->entityName, 'name');
            }

            $tag = $this->get('sulu_tag.tag_manager')->save(array('name' => $name), $this->getUser()->getId());

            $view = $this->view($tag, 200);
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException('A tag with the name "' . $exc->getName() . '"already exists!', 'name');
            $view = $this->view($cvExistsException->toArray(), 400);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
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

            $tag = $this->get('sulu_tag.tag_manager')->save(array('name' => $name), $this->getUser()->getId(), $id);

            $view = $this->view($tag, 200);
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException('A tag with the name "' . $exc->getName() . '"already exists!', 'name');
            $view = $this->view($cvExistsException->toArray(), 400);
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException($this->entityName, $id);
            $view = $this->view($entityNotFoundException->toArray(), 404);
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
            $srcTagIds = explode(',', $this->getRequest()->get('src'));
            $destTagId = $this->getRequest()->get('dest');

            $destTag = $this->get('sulu_tag.tag_manager')->merge($srcTagIds, $destTagId);

            $view = $this->view(null, 303, array('location' => $destTag->getLinks()['self']));
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException($this->entityName, $exc->getId());
            $view = $this->view($entityNotFoundException->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * TODO: find out why pluralization does not work for this patch action
     * ISSUE: https://github.com/sulu-cmf/SuluTagBundle/issues/6
     * @Route("/tags", name="tags")
     * updates an array of tags
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction()
    {

        try {

            $tags = array();

            /** @var Request $request */
            $request = $this->getRequest();
            $i = 0;
            while ($item = $request->get($i)) {
                if (isset($item['id'])) {
                    $tags[] = $this->get('sulu_tag.tag_manager')->save($item, $item['id']);
                } else {
                    $tags[] = $this->get('sulu_tag.tag_manager')->save($item, null);
                }
                $i++;
            }
            $this->getDoctrine()->getManager()->flush();
            $view = $this->view($tags, 200);

        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException('A tag with the name "' . $exc->getName() . '"already exists!', 'name');
            $view = $this->view($cvExistsException->toArray(), 400);
        }

        return $this->handleView($view);
    }


}
