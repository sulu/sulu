<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CollectionBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;

use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\ContactBundle\Entity\Media;


use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;

/**
 * Makes collections available through a REST API
 * @package Sulu\Bundle\MediaBUndle\Controller
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
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'media.collection.';


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
     * persists a setting
     * @Put("accounts/fields")
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
     * Creates a new collection
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $collection = new Collection();

            $collection->setStyle($this->getRequest()->get('style'));
            $collection->setCollectionType($this->getRequest()->get('idCollectionTypes'));

            $parentData = $this->getRequest()->get('parent');
            if ($parentData != null && isset($parentData['id']) && $parentData['id'] != 'null' && $parentData['id'] != '') {
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

            $metas = $this->getRequest()->get('metas');
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
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id)
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


                // set parent
                $parentData = $this->getRequest()->get('parent');
                if ($parentData != null && isset($parentData['id']) && $parentData['id'] != 'null' && $parentData['id'] != '') {
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
                if (!($this->proccessMetas($collection))
                ) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                $em->flush();
                $view = $this->view($collection, 200);
            }

        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }



    /**
     * Delete an account with the given id
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
                ->findCollectionByIdAndDelete($id);

            if (!$collection) {
                throw new EntityNotFoundException($entityName, $id);
            }

            // do not allow to delete entity if child is existent
            if (!$collection->getChildren()->count()) {
                // return 405 error
            }

            $em = $this->getDoctrine()->getManager();

            // remove related media if removeMedias is true
            if (!is_null($this->getRequest()->get('removeMedias')) &&
                $this->getRequest()->get('removeMedias') == "true"
            ) {
                foreach ($collection->getMedias() as $media) {
                    $em->remove($media);
                }
            }

            $em->remove($collection);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all metas from request
     * @param Collection $collection The collection on which is worked
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function proccessMetas(Collection $collection)
    {
        $metas = $this->getRequest()->get('metas');

        $delete = function ($meta) use ($collection) {
            $collection->removeMeta($meta);

            return true;
        };

        $update = function ($meta, $matchedEntry) {
            return $this->updateMeta($meta, $matchedEntry);
        };

        $add = function ($url) use ($collection) {
            $this->addMetas($collection, $url);

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

        if (isset($urlData['id'])) {
            throw new EntityIdAlreadySetException($metaEntity, $metaData['id']);
        } else {
            $meta = new CollectionMeta();
            $meta->setCollection($collection->getId());
            $meta->setTitle($meta['title']);
            $meta->setDescription($meta['description']);
            $meta->setLocale($meta['locale']);

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
        $meta->setLocale($entry['local']);

        return $success;
    }
}