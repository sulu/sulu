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

use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\UploadFileException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
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
        $userLocale = $this->getUser()->getLocale();
        $locale = $request->get('locale');
        if ($locale) {
            $userLocale = $locale;
        }

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
            $view = $this->view(
                array_merge(
                    array(
                        '_links' => array(
                            'self' => $request->getRequestUri()
                        )
                    ),
                    $this->flatMedia($media, $userLocale)
                )
                , 200);
        }

        return $this->handleView($view);
    }

    /**
     * lists all medias
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $userLocale = $this->getUser()->getLocale();
        $locale = $request->get('locale');
        if ($locale) {
            $userLocale = $locale;
        }

        $collection = $request->get('collection');

        $medias = $this->getDoctrine()->getRepository($this->entityName)->findMedias($collection);

        $collections = $this->flatMedias($medias, $userLocale);

        $view = $this->view($this->createHalResponse($collections), 200);

        return $this->handleView($view);
    }

    /**
     * flat the collection array
     * @param $media
     * @param $locale
     * @return array
     */
    protected function flatMedia ($media, $locale)
    {
        $fileFormats = array( // TODO from config
            '170x170',
            '50x50'
        );
        $uploadPath = '/uploads/sulumedia'; // TODO from config
        $originPath = '/fileproxy'; // TODO from config;

        $flatMedia = array();
        $flatMedia['locale'] = $locale;
        foreach ($media as $key => $value) {
            $setKeyValue = true;
            switch ($key) {
                case 'collection':
                    if ($value) {
                        $value = $value['id'];
                    }
                    break;
                case 'changer':
                case 'creator':
                    if ($value) {
                        if (isset($value['contact']['firstName'])) {
                            $value = $value['contact']['firstName'] . ' ' . $value['contact']['lastName'];
                        }
                    }
                    break;
                case 'files':
                    if ($value) {
                        $file = $value[0];
                        $version = $file['version'];
                        $versions = array();
                        if ($file['fileVersions']) {
                            foreach ($file['fileVersions'] as $fileVersion) {
                                $versions = array(
                                    'id' => $fileVersion['id'],
                                    'version' => $fileVersion['version'],
                                );
                                if ($fileVersion['version'] == $version) {
                                    $flatMedia['name'] = $fileVersion['name'];
                                    $flatMedia['size'] = $fileVersion['size'];
                                    $flatMedia['version'] = $fileVersion['version'];
                                    $flatMedia['changed'] = $fileVersion['changed']->format('Y-m-d H:i:s');
                                    $flatMedia['created'] = $fileVersion['created']->format('Y-m-d H:i:s');
                                    $flatMedia['contentLanguages'] = $fileVersion['contentLanguages'];
                                    $flatMedia['publishLanguages'] = $fileVersion['publishLanguages'];
                                    $flatMedia['url'] = $originPath . '/' . $fileVersion['id'];
                                    $flatMedia['thumbnails'] = array();
                                    foreach ($fileFormats as $format) {
                                        $flatMedia['thumbnails'][] = array(
                                            'format' => $format,
                                            'url' => $uploadPath . '/'.$format.'/' . $fileVersion['name']
                                        );
                                    }

                                    if ($fileVersion['metas']) {
                                        $metaSet = false;
                                        foreach ($fileVersion['metas'] as $meta) {
                                            if ($meta['locale'] == $locale) {
                                                $metaSet = true;
                                                foreach ($meta as $metaKey => $metaValue) {
                                                    if (!in_array($metaKey, array('locale', 'id'))) {
                                                        $flatMedia[$metaKey] = $metaValue;
                                                    }
                                                }
                                            }
                                        }
                                        if (!$metaSet) {
                                            if (isset($fileVersion['metas'][0])) {
                                                foreach ($fileVersion['metas'][0] as $metaKey => $metaValue) {
                                                    if (!in_array($metaKey, array('locale', 'id'))) {
                                                        $flatMedia[$metaKey] = $metaValue;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    break;
                                }
                            }
                        }
                        $flatMedia['versions'] = $versions;
                    }
                    $setKeyValue = false;
                    break;
                default:
                    if (is_string($value) || is_int($value)) {
                        $flatMedia[$key] = $value;
                    } else {
                        $setKeyValue = false;
                    }
                    break;
            }
            if ($setKeyValue) {
                $flatMedia[$key] = $value;
            }
        }
        return $flatMedia;
    }

    /**
     * medias to an flat meda array
     * @param $medias
     * @param $locale
     * @return array
     */
    protected function flatMedias ($medias, $locale)
    {
        $flatMedias = array();

        foreach ($medias as $media) {
            $flatMedia = $this->flatMedia($media, $locale);
            array_push($flatMedias, $flatMedia);
        }

        return $flatMedias;
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
            // get collection id
            $collectionData = $request->get('collection');
            $collectionId = null;
            if ($this->checkDataForId($collectionData)) {
                $collectionId = $collectionData['id'];
            }

            // get fileversions properties
            $properties = array();
            $files = $request->get('files');
            if ($files) {
                $properties = $this->getProperties($files);
            }

            // generate media
            $uploadFiles = $this->getUploadedFiles($request, 'fileVersion');
            if (count($uploadFiles)) {
                foreach ($uploadFiles as $uploadFile) {
                    $media = $this->getMediaManager()->add($uploadFile, $this->getUser()->getId(), $collectionId, $properties);
                    break;
                }
            } else {
                throw new RestException('Uploaded file not found', UploadFileException::EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND);
            }

            $view = $this->view($media, 200);
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
            // get collection id
            $collectionData = $request->get('collection');
            $collectionId = null;
            if ($this->checkDataForId($collectionData)) {
                $collectionId = $collectionData['id'];
            }

            // get fileversions properties
            $properties = array();
            $files = $request->get('files');
            if ($files) {
                $properties = $this->getProperties($files);
            }

            // update media
            $uploadFiles = $this->getUploadedFiles($request, 'fileVersion');
            if (count($uploadFiles)) {
                // Add new Fileversion
                foreach ($uploadFiles as $uploadFile) {
                    $media = $this->getMediaManager()->update($uploadFile, $this->getUser()->getId(), $id, $collectionId, $properties);
                    break;
                }
            } else {
                // Update only properties
                $media = $this->getMediaManager()->update(null, $this->getUser()->getId(), $id, $collectionId, $properties);
            }

            $view = $this->view($media, 200);
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
     * Check given data for a not empty id
     * @param $data
     * @return bool
     */
    protected function checkDataForId($data)
    {
        if ($data != null && isset($data['id']) && $data['id'] != 'null' && $data['id'] != '') {
            return true;
        }
        return false;
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
     * @param $files
     * @return array
     */
    protected function getProperties($files)
    {
        $properties = array();

        if (isset($files['fileVersions'])) {
            $properties = $files['fileVersions'];
        }

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
}
