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

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;

use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaMeta;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;

use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use \DateTime;

/**
 * Makes medias available through a REST API
 * @package Sulu\Bundle\MediaBundle\Controller
 */
class MediaController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluMediaBundle:Media';

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('created');

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
    protected $bundlePrefix = 'media.medias.';


    /**
     * returns all fields that can be used by list
     * @Get("medias/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
    }

    /**
     * persists a setting
     * @Put("medias/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single media with the given id
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
                    ->findMediaById($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all medias
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        $medias = $this->getDoctrine()->getRepository($this->entityName)->findAll();
        $view = $this->view($this->createHalResponse($medias), 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new media
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $media = new Media();

            // set collection
            $collectionData = $this->getRequest()->get('collection');
            if ($collectionData != null && isset($collectionData['id']) && $collectionData['id'] != 'null' && $collectionData['id'] != '') {
                $collection = $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->findCollectionById($collectionData['id']);
                if (!$collection) {
                    throw new EntityNotFoundException('SuluMediaBundle:Collection', $collectionData['id']);
                }
                $media->setCollection($collection);
            }

            // TODO

            // set creator / changer
            $media->setCreated(new DateTime());
            $media->setChanged(new DateTime());
            $media->setCreator($this->getUser());
            $media->setChanger($this->getUser());

            // set metas
            $metas = $this->getRequest()->get('metas');
            if (!empty($metas)) {
                foreach ($metas as $metaData) {
                    $this->addMetas($media, $metaData);
                }
            }

            // TODO set files

            $em->persist($media);

            $em->flush();

            $view = $this->view($media, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing media with the given id
     * @param integer $id The id of the media to update
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id)
    {
        $mediaEntity = 'SuluMediaBundle:Media';

        try {
            /** @var Media $media */
            $media = $this->getDoctrine()
                ->getRepository($mediaEntity)
                ->findMediaById($id);

            if (!$media) {
                throw new EntityNotFoundException($mediaEntity, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                // set collection
                $collectionData = $this->getRequest()->get('collection');
                if ($collectionData != null && isset($collectionData['id']) && $collectionData['id'] != 'null' && $collectionData['id'] != '') {
                    $collection = $this->getDoctrine()
                        ->getRepository($this->entityName)
                        ->findCollectionById($collectionData['id']);
                    if (!$collection) {
                        throw new EntityNotFoundException('SuluMediaBundle:Collection', $collectionData['id']);
                    }
                    $media->setCollection($collection);
                }

                // TODO

                // set changed
                $media->setChanged(new DateTime());
                $user = $this->getUser();
                $media->setChanger($user);

                // set metas
                if (!$this->processMetas($media)) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                // TODO set files?

                $em->flush();
                $view = $this->view($media, 200);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Delete a media with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $entityName = 'SuluMediaBundle:Media';

            /* @var Media $media */
            $media = $this->getDoctrine()
                ->getRepository($entityName)
                ->findMediaByIdForDelete($id);

            if (!$media) {
                throw new EntityNotFoundException($entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();

            $em->remove($media);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all metas from request
     * @param Media $media The media on which is worked
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processMetas(Media $media)
    {
        $metas = $this->getRequest()->get('metas');

        $delete = function ($meta) use ($media) {
            $media->removeMeta($meta);

            return true;
        };

        $update = function ($meta, $matchedEntry) {
            return $this->updateMeta($meta, $matchedEntry);
        };

        $add = function ($meta) use ($media) {
            $this->addMetas($media, $meta);

            return true;
        };

        return $this->processPut($media->getMetas(), $metas, $delete, $update, $add);
    }

    /**
     * Adds META to a media
     * @param Media $media
     * @param $metaData
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addMetas(Media $media, $metaData)
    {
        $em = $this->getDoctrine()->getManager();
        $metaEntity = 'SuluMediaBundle:MediaMeta';

        if (isset($urlData['id'])) {
            throw new EntityIdAlreadySetException($metaEntity, $metaData['id']);
        } else {
            $meta = new MediaMeta();
            $meta->setMedia($media);
            $meta->setTitle($metaData['title']);
            $meta->setDescription($metaData['description']);
            $meta->setLocale($metaData['locale']);

            $em->persist($meta);
            $media->addMeta($meta);
        }
    }

    /**
     * Updates the given meta
     * @param MediaMeta $meta The media meta object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function updateMeta(MediaMeta $meta, $entry)
    {
        $success = true;

        $meta->setTitle($entry['title']);
        $meta->setDescription($entry['description']);
        $meta->setLocale($entry['locale']);

        return $success;
    }
}
