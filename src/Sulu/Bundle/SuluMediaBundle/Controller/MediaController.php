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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;

use Sulu\Bundle\MediaBundle\Media\Exception\UploadFileException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\RestObject\Media;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes media available through a REST API
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
    protected $fieldsHidden = array('id', 'created', 'changed');

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
    protected $fieldsRelations = array('title', 'name', 'description', 'thumbnails', 'size');

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'media.media.';

    /**
     * returns all fields that can be used by list
     * @Get("media/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
    }

    /**
     * persists a setting
     * @Put("media/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single media with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getLocale($request->get('locale'));

        $mediaEntity = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findMediaById($id, true);

        if (!$mediaEntity) {
            $exception = new EntityNotFoundException($this->entityName, $id);
            // Return a 404 together with an error message, given by the exception, if the entity is not found
            $view = $this->view(
                $exception->toArray(),
                404
            );
        } else {
            $media = new Media();

            $view = $this->view(
                array_merge(
                    array(
                        '_links' => array(
                            'self' => $request->getRequestUri()
                        )
                    ),
                    $media->setDataByEntityArray($mediaEntity, $locale, $request->get('version', null))->toArray()
                )
                , 200);
        }

        return $this->handleView($view);
    }

    /**
     * lists all media
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getLocale($request->get('locale'));

        $collection = $request->get('collection');
        $fields = $request->get('fields', null);
        if ($fields !== null) {
            $fields = explode(',', $fields);
        }
        $mediaList = $this->getDoctrine()->getRepository($this->entityName)->findMedia($collection);
        $mediaList = $this->flatMedia($mediaList, $locale, $fields);
        $view = $this->view($this->createHalResponse($mediaList), 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new media
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException
     */
    public function postAction(Request $request)
    {
        try {
            // locale
            $locale = $this->getLocale($request->get('locale'));

            // get collection id
            $media = $this->getRestObject($request);

            // get fileversions properties
            $properties = $this->getProperties($media);

            // generate media
            $uploadFiles = $this->getUploadedFiles($request, 'fileVersion');
            if (count($uploadFiles)) {
                foreach ($uploadFiles as $uploadFile) {
                    $mediaEntity = $this->getMediaManager()->add($uploadFile, $this->getUser()->getId(), $media->getCollection(), $properties);
                    break;
                }
            } else {
                throw new RestException('Uploaded file not found', UploadFileException::EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND);
            }

            $media = new Media();
            $view = $this->view($media->setDataByEntity($mediaEntity, $locale)->toArray(), 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        } catch (UploadFileException $ufe) {
            $view = $this->view($ufe->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing media with the given id
     * @param integer $id The id of the media to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        try {
            // locale
            $locale = $this->getLocale($request->get('locale'));

            // get collection id
            $media = $this->getRestObject($request);

            // get fileversions properties
            $properties = $this->getProperties($media);

            // update media
            $uploadFiles = $this->getUploadedFiles($request, 'fileVersion');
            if (count($uploadFiles)) {
                // Add new Fileversion
                foreach ($uploadFiles as $uploadFile) {
                    $mediaEntity = $this->getMediaManager()->update($uploadFile, $this->getUser()->getId(), $id, $media->getCollection(), $properties);
                    break;
                }
            } else {
                // Update only properties
                $mediaEntity = $this->getMediaManager()->update(null, $this->getUser()->getId(), $id, $media->getCollection(), $properties);
            }

            $media = new Media();
            $view = $this->view($media->setDataByEntity($mediaEntity, $locale)->toArray(), 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (UploadFileException $ufe) {
            $view = $this->view($ufe->toArray(), 400);
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
            $this->getMediaManager()->remove($id, $this->getUser()->getId());
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @return Media
     */
    protected function getRestObject(Request $request)
    {
        $object = new Media();
        $object->setId($request->get('id'));
        $object->setLocale($request->get('locale', $this->getLocale($request->get('locale'))));
        $object->setType($request->get('type'));
        $object->setCollection($request->get('collection'));
        $object->setVersions($request->get('versions', array()));
        $object->setVersion($request->get('version'));
        $object->setSize($request->get('size'));
        $object->setContentLanguages($request->get('contentLanguages', array()));
        $object->setPublishLanguages($request->get('publishLanguages', array()));
        $object->setTags($request->get('tags', array()));
        $object->setThumbnails($request->get('thumbnails', array()));
        $object->setUrl($request->get('url'));
        $object->setName($request->get('name'));
        $object->setTitle($request->get('title', $this->getTitleFromUpload($request, 'fileVersion')));
        $object->setDescription($request->get('description'));
        $object->setChanger($request->get('changer'));
        $object->setCreator($request->get('creator'));
        $object->setChanged($request->get('changed'));
        $object->setCreated($request->get('created'));

        return $object;
    }

    /**
     * @param Request $request
     * @return null
     */
    protected function getTitleFromUpload($request)
    {
        $title = null;

        /**
         * @var UploadedFile $uploadedFile
         */
        foreach ($this->getUploadedFiles($request, 'fileVersion') as $uploadedFile) {
            $title = $part   = implode('.', explode('.', $uploadedFile->getClientOriginalName(), -1));;
            break;
        }

        return $title;
    }

    /**
     * convert media entities array to flat media rest object array
     * @param $mediaList
     * @param $locale
     * @param array $fields
     * @return array
     */
    protected function flatMedia ($mediaList, $locale, $fields = array())
    {
        $flatMediaList = array();

        foreach ($mediaList as $media) {
            $flatMedia = new Media();
            $flatMediaList[] = $flatMedia->setDataByEntityArray($media, $locale)->toArray($fields);
        }

        return $flatMediaList;
    }

    /**
     * get uploaded file when name is 'file' or 'file[]'
     * @param Request $request
     * @param $name
     * @return array
     */
    private function getUploadedFiles(Request $request, $name)
    {
        if (is_null($request->files->get($name))) {
            return array();
        }

        if (is_array($request->files->get($name))) {
            return $request->files->get($name);
        }

        return array(
            $request->files->get($name)
        );
    }

    /**
     * give back the fileversion properties
     * @param MediaRestObject $restObject
     * @return array
     */
    protected function getProperties($restObject)
    {
        $properties = array();

        $fileVersion = array();
        $fileVersion['version'] = $restObject->getVersion();

        if ($restObject->getContentLanguages() && count($restObject->getContentLanguages())) {
            $fileVersion['contentLanguages'] = $restObject->getContentLanguages();
        }

        if ($restObject->getPublishLanguages() && count($restObject->getPublishLanguages())) {
            $fileVersion['publishLanguages'] = $restObject->getPublishLanguages();
        }

        if ($restObject->getTags() && count($restObject->getTags())) {
            $fileVersion['tags'] = $restObject->getTags();
        }

        if ($restObject->getLocale() && $restObject->getTitle()) {
            $meta = array();
            $meta['title'] = $restObject->getTitle();
            $meta['locale'] = $restObject->getLocale();
            if ($restObject->getDescription()) {
                $meta['description'] = $restObject->getDescription();
            }
            $fileVersion['meta'] = array();
            $fileVersion['meta'][] = $meta;
        }
        $properties[] = $fileVersion;

        return $properties;
    }

    /**
     * getMediaManager
     * @return MediaManagerInterface
     */
    protected function getMediaManager()
    {
        return $this->get('sulu_media.media_manager');
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
