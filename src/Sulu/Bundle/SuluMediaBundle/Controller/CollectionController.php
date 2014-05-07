<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use DateTime;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes collections available through a REST API
 * @package Sulu\Bundle\MediaBundle\Controller
 */
class CollectionController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluMediaBundle:Collection';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsDefault = array('name');

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('created');

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(0 => 'id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array('id' => 'public.id');

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
     *
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'media.collection.';

    /**
     * returns all fields that can be used by list
     * @Get("collection/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
    }

    /**
     * persists a setting
     * @Put("collection/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single collection with the given id
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
                    ->findCollectionById($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all collections
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        $collections = $this->getDoctrine()->getRepository($this->entityName)->findAll();
        $view = $this->view($this->createHalResponse($collections), 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new collection
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $collection = new Collection();

            // set style
            $collection->setStyle($request->get('style'));

            // set type
            $typeData = $request->get('type');

            // ??? is this check really necessary ???
            if ($typeData != null && isset($typeData['id']) && $typeData['id'] != 'null' && $typeData['id'] != '') {
                /** @var CollectionType $type */
                $type = $this->getDoctrine()->getRepository('SuluMediaBundle:CollectionType')->find($typeData['id']);

                if (!$type) {
                    throw new EntityNotFoundException($this->entityName, $typeData['id']);
                }

                $collection->setType($type);
            }

            // set parent
            $parentData = $request->get('parent');
            if ($parentData != null && isset($parentData['id']) && $parentData['id'] != 'null' && $parentData['id'] != '') {
                /** @var Collection $parent */
                $parent = $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->findCollectionById($parentData['id']);

                if (!$parent) {
                    throw new EntityNotFoundException($this->entityName, $parentData['id']);
                }

                $collection->setParent($parent);
            }

            // set creator / changer
            $collection->setCreated(new DateTime());
            $collection->setChanged(new DateTime());
            $collection->setCreator($this->getUser());
            $collection->setChanger($this->getUser());

            // set metas
            $metas = $request->get('metas');
            if (!empty($metas)) {
                foreach ($metas as $metaData) {
                    $this->addMetas($collection, $metaData);
                }
            }

            $em->persist($collection);

            $em->flush();

            $view = $this->view($collection, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing collection with the given id
     * @param integer $id The id of the collection to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        $collectionEntity = 'SuluMediaBundle:Collection';

        try {
            /** @var Collection $collection */
            $collection = $this->getDoctrine()
                ->getRepository($collectionEntity)
                ->findCollectionById($id);

            if (!$collection) {
                throw new EntityNotFoundException($collectionEntity, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                // set style
                $collection->setStyle($request->get('style'));

                // set type
                $typeData = $request->get('type');

                //
                // @alexander-schranz
                // please remove code duplication!
                // same code used in postAction
                //

                // ??? is this check really necessary ???
                if ($typeData != null && isset($typeData['id']) && $typeData['id'] != 'null' && $typeData['id'] != '') {
                    /** @var CollectionType $type */
                    $type = $this->getDoctrine()->getRepository('SuluMediaBundle:CollectionType')->find($typeData['id']);

                    if (!$type) {
                        throw new EntityNotFoundException($this->entityName, $typeData['id']);
                    }

                    $collection->setType($type);
                }

                // set parent
                $parentData = $request->get('parent');
                if ($parentData != null && isset($parentData['id']) && $parentData['id'] != 'null' && $parentData['id'] != '') {
                    /** @var Collection $parent */
                    $parent = $this->getDoctrine()
                        ->getRepository($this->entityName)
                        ->findCollectionById($parentData['id']);

                    if (!$parent) {
                        throw new EntityNotFoundException($this->entityName, $parentData['id']);
                    }

                    $collection->setParent($parent);
                } else {
                    $collection->setParent(null);
                }

                // set changed
                $collection->setChanged(new DateTime());
                $user = $this->getUser();
                $collection->setChanger($user);

                // process details
                if (!$this->processMetas($collection, $request->get('metas'))) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                $em->flush();
                $view = $this->view($collection, 200);
            }
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Delete a collection with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $entityName = 'SuluMediaBundle:Collection';

            /* @var Collection $collection */
            $collection = $this->getDoctrine()
                ->getRepository($entityName)
                ->findCollectionByIdForDelete($id);

            if (!$collection) {
                throw new EntityNotFoundException($entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();

            $em->remove($collection);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all metas from request
     * @param Collection $collection The collection on which is worked
     * @param mixed $metas Request meta data to process
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processMetas(Collection $collection, $metas)
    {
        $delete = function ($meta) use ($collection) {
            $collection->removeMeta($meta);

            return true;
        };

        $update = function ($meta, $matchedEntry) {
            return $this->updateMeta($meta, $matchedEntry);
        };

        $add = function ($meta) use ($collection) {
            $this->addMetas($collection, $meta);

            return true;
        };

        return $this->processPut($collection->getMetas(), $metas, $delete, $update, $add);
    }

    /**
     * Adds META to a collection
     * @param Collection $collection
     * @param $metaData
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addMetas(Collection $collection, $metaData)
    {
        $em = $this->getDoctrine()->getManager();
        $metaEntity = 'SuluMediaBundle:CollectionMeta';

        if (isset($metaData['id'])) {
            throw new EntityIdAlreadySetException($metaEntity, $metaData['id']);
        } else {
            $meta = new CollectionMeta();
            $meta->setCollection($collection);
            $meta->setTitle($metaData['title']);
            $meta->setDescription($metaData['description']);
            $meta->setLocale($metaData['locale']);

            $em->persist($meta);
            $collection->addMeta($meta);
        }
    }

    /**
     * Updates the given meta
     * @param CollectionMeta $meta The collection meta object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function updateMeta(CollectionMeta $meta, $entry)
    {
        $success = true;

        $meta->setTitle($entry['title']);
        $meta->setDescription($entry['description']);
        $meta->setLocale($entry['locale']);

        return $success;
    }
}
