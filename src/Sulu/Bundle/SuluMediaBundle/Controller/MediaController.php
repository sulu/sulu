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

use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;

use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\UploadFileException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\RestObject\MediaRestObject;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use \DateTime;
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

        $media = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findMediaById($id, true);

        if (!$media) {
            $exception = new EntityNotFoundException($this->entityName, $id);
            // Return a 404 together with an error message, given by the exception, if the entity is not found
            $view = $this->view(
                $exception->toArray(),
                404
            );
        } else {
            $mediaRestObject = new MediaRestObject();

            $view = $this->view(
                array_merge(
                    array(
                        '_links' => array(
                            'self' => $request->getRequestUri()
                        )
                    ),
                    $mediaRestObject->setDataByEntityArray($media, $locale, $request->get('version', null))
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
        $mediaList = $this->getDoctrine()->getRepository($this->entityName)->findMedia($collection);
        $mediaList = $this->flatMedia($mediaList, $locale, $request->get('fields', array()));
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
            $mediaRestObject = $this->getRestObject($request);

            // get fileversions properties
            $properties = $this->getProperties($mediaRestObject);

            // generate media
            $uploadFiles = $this->getUploadedFiles($request, 'fileVersion');
            if (count($uploadFiles)) {
                foreach ($uploadFiles as $uploadFile) {
                    $media = $this->getMediaManager()->add($uploadFile, $this->getUser()->getId(), $mediaRestObject->getCollection(), $properties);
                    break;
                }
            } else {
                throw new RestException('Uploaded file not found', UploadFileException::EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND);
            }

            $mediaRestObject = new MediaRestObject();
            $view = $this->view($mediaRestObject->setDataByEntity($media, $locale)->toArray(), 200);
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
            $mediaRestObject = $this->getRestObject($request);

            // get fileversions properties
            $properties = $this->getProperties($mediaRestObject);

            // update media
            $uploadFiles = $this->getUploadedFiles($request, 'fileVersion');
            if (count($uploadFiles)) {
                // Add new Fileversion
                foreach ($uploadFiles as $uploadFile) {
                    $media = $this->getMediaManager()->update($uploadFile, $this->getUser()->getId(), $id, $mediaRestObject->getCollection(), $properties);
                    break;
                }
            } else {
                // Update only properties
                $media = $this->getMediaManager()->update(null, $this->getUser()->getId(), $id, $mediaRestObject->getCollection(), $properties);
            }

            $mediaRestObject = new MediaRestObject();
            $view = $this->view($mediaRestObject->setDataByEntity($media, $locale)->toArray(), 200);
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
     * @return MediaRestObject
     */
    protected function getRestObject(Request $request)
    {
        $object = new MediaRestObject();
        $object->setId($request->get('id'));
        $object->setLocale($request->get('locale', $this->getLocale($request->get('locale'))));
        $object->setType($request->get('type'));
        $object->setCollection($request->get('collection'));
        $object->setVersions($request->get('versions', array()));
        $object->setLocale($request->get('version', 1));
        $object->setSize($request->get('size'));
        $object->setContentLanguages($request->get('contentLanguages', array()));
        $object->setPublishLanguages($request->get('publishLanguages', array()));
        $object->setTags($request->get('tags', array()));
        $object->setThumbnails($request->get('thumbnails', array()));
        $object->setUrl($request->get('url'));
        $object->setName($request->get('name'));
        $object->setTitle($request->get('title'));
        $object->setDescription($request->get('description'));
        $object->setChanger($request->get('changer'));
        $object->setCreator($request->get('creator'));
        $object->setChanged($request->get('changed'));
        $object->setCreated($request->get('created'));

        return $object;
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
            $flatMedia = new MediaRestObject();
            array_push($flatMediaList, $flatMedia->setDataByEntityArray($media, $locale)->toArray($fields));
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
