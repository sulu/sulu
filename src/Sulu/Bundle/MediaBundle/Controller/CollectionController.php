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
use Sulu\Bundle\MediaBundle\Media\RestObject\CollectionRestObject;
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
    protected $fieldsDefault = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('');

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
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $collection = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findCollectionById($id, true);

        if (!$collection) {
            $exception = new EntityNotFoundException($this->entityName, $id);
            // Return a 404 together with an error message, given by the exception, if the entity is not found
            $view = $this->view(
                $exception->toArray(),
                404
            );
        } else {
            $locale = $this->getLocale($request->get('locale'));
            $collectionRestObject = new CollectionRestObject();

            $view = $this->view(
                array_merge(
                    array(
                        '_links' => array(
                            'self' => $request->getRequestUri()
                        )
                    ),
                    $collectionRestObject->setDataByEntityArray($collection, $locale)->toArray()
                )
                , 200);
        }

        return $this->handleView($view);
    }

    /**
     * lists all collections
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getLocale($request->get('locale'));

        $parentId = $request->get('parent');
        $depth = $request->get('depth');

        $collections = $this->getDoctrine()->getRepository($this->entityName)->findCollections($parentId, $depth);
        $collections = $this->flatCollections($collections, $locale, $request->get('fields', array()));
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

            $collectionRestObject = $this->getRestObject($request);

            $collection = new Collection();
            $collection->setCreated(new DateTime());
            $collection->setCreator($this->getUser());
            $this->createCollectionByRestObject($collectionRestObject, $collection, $em);

            $em->persist($collection);
            $em->flush();

            $locale = $this->getLocale($request->get('locale'));

            $collectionRestObject = new CollectionRestObject();

            $view = $this->view($collectionRestObject->setDataByEntity($collection, $locale)->toArray(), 200);
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

                $collectionRestObject = $this->getRestObject($request);
                $this->createCollectionByRestObject($collectionRestObject, $collection, $em);

                $em->persist($collection);
                $em->flush();

                $collectionRestObject = new CollectionRestObject();
                $locale = $this->getLocale($request->get('locale'));

                $view = $this->view($collectionRestObject->setDataByEntity($collection, $locale)->toArray(), 200);
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
     * convert a collections array to an array of collection rest objects
     * @param $collections
     * @param $locale
     * @param array $fields
     * @return array
     */
    protected function flatCollections ($collections, $locale, $fields = array())
    {
        $flatCollections = array();

        foreach ($collections as $collection) {
            $flatCollection = new CollectionRestObject();
            array_push($flatCollections, $flatCollection->setDataByEntityArray($collection, $locale, $fields));
        }

        return $flatCollections;
    }

    /**
     * @param Request $request
     * @return CollectionRestObject
     */
    protected function getRestObject(Request $request)
    {
        $collectionRestObject = new CollectionRestObject();
        $collectionRestObject->setId($request->get('id'));
        $collectionRestObject->setStyle($request->get('style'));
        $collectionRestObject->setType($request->get('type'));
        $collectionRestObject->setParent($request->get('parent'));
        $collectionRestObject->setLocale($request->get('locale'));
        $collectionRestObject->setTitle($request->get('title'));
        $collectionRestObject->setDescription($request->get('description'));
        $collectionRestObject->setChanger($request->get('changer'));
        $collectionRestObject->setCreator($request->get('creator'));
        $collectionRestObject->setChanged($request->get('changed'));
        $collectionRestObject->setCreated($request->get('created'));

        return $collectionRestObject;
    }

    /**
     * @param CollectionRestObject $object
     * @param Collection $collection
     * @param $em
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function createCollectionByRestObject(CollectionRestObject $object, Collection &$collection, &$em)
    {
        // Set Style
        $collection->setStyle(json_encode($object->getStyle()));

        // Set Type
        $type = $this->getDoctrine()->getRepository('SuluMediaBundle:CollectionType')->find($object->getType());
        if (!$type) {
            throw new EntityNotFoundException($this->entityName, $object->getType());
        }
        $collection->setType($type);

        // Set Parent
        if ($object->getParent()) {
            // / @var Collection $parent
            $parent = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findCollectionById($object->getParent());

            if (!$parent) {
                throw new EntityNotFoundException($this->entityName, $object->getParent());
            }
            $collection->setParent($parent);
        } else {
            $collection->setParent(null);
        }

        $collection->setChanged(new DateTime());
        $collection->setChanger($this->getUser());

        // set Meta
        $metaSet = false;
        if ($object->getTitle()) {
            foreach ($collection->getMeta() as $meta) {
                /**
                 * @var CollectionMeta $meta
                 */
                if ($meta->getLocale() == $object->getLocale()) {
                    $metaSet = true;
                    $meta->setTitle($object->getTitle());
                    $meta->setDescription($object->getDescription());
                    $em->persist($meta);
                }
            }
            if (!$metaSet) {
                $meta = new CollectionMeta();
                $meta->setLocale($object->getLocale());
                $meta->setTitle($object->getTitle());
                $meta->setDescription($object->getDescription());
                $meta->setCollection($collection);
                $collection->addMeta($meta);
                $em->persist($meta);
            }
        }
    }

    /**
     * @param $requestLocale
     * @return mixed
     */
    protected function getLocale($requestLocale)
    {
        if ($requestLocale) {
            return $requestLocale;
        }

        return $this->getUser()->getLocale();
    }
}
