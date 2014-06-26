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
use Sulu\Bundle\MediaBundle\Media\RestObject\Collection;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
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
     * @var string
     */
    protected $entityMediaName = 'SuluMediaBundle:Media';

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
    protected $fieldsHidden = array();

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
        $collectionEntity = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findCollectionById($id, true);

        if (!$collectionEntity) {
            $exception = new EntityNotFoundException($this->entityName, $id);
            // Return a 404 together with an error message, given by the exception, if the entity is not found
            $view = $this->view(
                $exception->toArray(),
                404
            );
        } else {
            $locale = $this->getLocale($request->get('locale'));
            $collection = new Collection();
            $collection->setDataByEntityArray($collectionEntity, $locale);
            $collection->setPreviews($this->getPreviews($collection->getId()));

            $view = $this->view(
                array_merge(
                    array(
                        '_links' => array(
                            'self' => $request->getRequestUri()
                        )
                    ),
                    $collection->toArray()
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

        $collectionEntities = $this->getDoctrine()->getRepository($this->entityName)->findCollections($parentId, $depth);
        $collections = $this->flatCollections($collectionEntities, $locale, $request->get('fields', array()));
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

            $collection = $this->getRestObject($request);

            $collectionEntity = new CollectionEntity();
            $collectionEntity->setCreated(new DateTime());
            $collectionEntity->setCreator($this->getUser());
            $this->createCollectionByRestObject($collection, $collectionEntity, $em);

            $em->persist($collectionEntity);
            $em->flush();

            $locale = $this->getLocale($request->get('locale'));
            $collection = new Collection();
            $collection->setDataByEntity($collectionEntity, $locale);
            $collection->setPreviews($this->getPreviews($collection->getId()));

            $view = $this->view($collection->toArray(), 200);
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
        try {
            /** @var CollectionEntity $collection */
            $collectionEntity = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findCollectionById($id);

            if (!$collectionEntity) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $collection = $this->getRestObject($request);
                $this->createCollectionByRestObject($collection, $collectionEntity, $em);

                $em->persist($collectionEntity);
                $em->flush();

                $locale = $this->getLocale($request->get('locale'));
                $collection = new Collection();
                $collection->setDataByEntity($collectionEntity, $locale);
                $collection->setPreviews($this->getPreviews($collection->getId()));

                $view = $this->view($collection->toArray(), 200);
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
            /* @var CollectionEntity $collection */
            $collectionEntity = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findCollectionByIdForDelete($id);

            if (!$collectionEntity) {
                throw new EntityNotFoundException($this->entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();

            $em->remove($collectionEntity);
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
    protected function flatCollections($collections, $locale, $fields = array())
    {
        $flatCollections = array();

        foreach ($collections as $collection) {
            $flatCollection = new Collection();
            $flatCollection->setDataByEntityArray($collection, $locale, $fields);
            $flatCollection->setPreviews($this->getPreviews($flatCollection->getId()));
            array_push($flatCollections, $flatCollection->toArray());
        }

        return $flatCollections;
    }

    /**
     * @param Request $request
     * @return Collection
     */
    protected function getRestObject(Request $request)
    {
        $collection = new Collection();
        $collection->setId($request->get('id'));
        $collection->setStyle($request->get('style'));
        $collection->setType($request->get('type', $this->container->getParameter('sulu_media.collection.type.default')));
        $collection->setParent($request->get('parent'));
        $collection->setLocale($request->get('locale', $this->getLocale($request->get('locale'))));
        $collection->setTitle($request->get('title'));
        $collection->setDescription($request->get('description'));
        $collection->setChanger($request->get('changer'));
        $collection->setCreator($request->get('creator'));
        $collection->setChanged($request->get('changed'));
        $collection->setCreated($request->get('created'));

        return $collection;
    }

    /**
     * @param Collection $object
     * @param CollectionEntity $collection
     * @param $em
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function createCollectionByRestObject(Collection $object, CollectionEntity &$collection, &$em)
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
            /** @var CollectionEntity $parent */
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
                    if ($object->getDescription()) {
                        $meta->setDescription($object->getDescription());
                    }
                    $meta->setLocale($object->getLocale());
                    $em->persist($meta);
                }
            }
            if (!$metaSet) {
                $meta = new CollectionMeta();
                $meta->setTitle($object->getTitle());
                $meta->setLocale($object->getLocale());
                if ($object->getDescription()) {
                    $meta->setDescription($object->getDescription());
                }
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

    /**
     * getFormatManager
     * @return FormatManagerInterface
     */
    protected function getFormatManager()
    {
        return $this->get('sulu_media.format_manager');
    }

    /**
     * @param $id
     * @return array
     */
    protected function getPreviews($id)
    {
        $formats = array();

        $medias = $this->getDoctrine()
            ->getRepository($this->entityMediaName)
            ->findMedia($id, null, $this->container->getParameter('sulu_media.collection.previews.limit'));


        foreach ($medias as $media) {
            foreach ($media['files'] as $file) {
                foreach ($file['fileVersions'] as $fileVersion) {
                    if ($fileVersion['version'] == $file['version']) {
                        $format = $this->getPreviewsFromFileVersion($media['id'], $fileVersion);
                        if (!empty($format)) {
                            $formats[] = $format;
                        }
                        break;
                    }
                }
                break;
            }
        }

        return $formats;
    }

    /**
     * @param int $mediaId
     * @param array $fileVersion
     * @return array
     */
    protected function getPreviewsFromFileVersion($mediaId, $fileVersion)
    {
        $title = '';
        foreach ($fileVersion['meta'] as $key => $meta) {
            if ($meta['locale'] == $this->getUser()->getLocale()) {
                $title = $meta['title'];
                break;
            } elseif ($key == 0) { // fallback title
                $title = $meta['title'];
            }
        }

        $mediaFormats = $this->getFormatManager()->getFormats($mediaId, $fileVersion['name'], $fileVersion['storageOptions']);

        foreach ($mediaFormats as $formatName => $formatUrl) {
            if ($formatName == $this->container->getParameter('sulu_media.collection.previews.format')) {
                return array(
                    'url' => $formatUrl,
                    'title' => $title
                );
                break;
            }
        }

        return array();
    }


}
