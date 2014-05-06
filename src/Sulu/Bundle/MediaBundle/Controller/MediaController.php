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
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

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
     * @var int
     * @description this exception code is set when $_FILES['error'] > 0
     */
    const EXCEPTION_CODE_UPLOAD_ERROR = 5001;

    /**
     * @var int
     * @description this exception code is set when the uploaded file was not found
     */
    const EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND = 5002;

    /**
     * @var int
     * @description this exception code is set when the file is bigger as the max file size in the config
     */
    const EXCEPTION_CODE_MAX_FILE_SIZE = 5003;

    /**
     * @var int
     * @description this exception code is set when the file type is not supported
     */
    const EXCEPTION_CODE_BLOCKED_FILE_TYPE = 5004;

    /*
     * File Sizes
     */
    const B = 1;
    const KB = 1024;
    const MB = 1048576;
    const GB = 1073741824;
    const TB = 1099511627776;

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
     * lists all medias
     * @Get("media", name="get_all_media")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        $medias = $this->getDoctrine()->getRepository($this->entityName)->findAll();
        $view = $this->view($this->createHalResponse($medias), 200);

        return $this->handleView($view);
    }

    /**
     * Shows a single media with the given id
     * @Get("media/{id}", name="get_single_media")
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

            // set file
            $file = new File();
            $file->setCreated(new DateTime());
            $file->setChanged(new DateTime());
            $file->setCreator($this->getUser());
            $file->setChanger($this->getUser());

            // set fileVersions
            $versionCounter = 0;
            if (!empty($_FILES['fileVersion'])) {
                foreach ($this->getUploadedFiles('fileVersion') as $fileVersionData) {
                    $this->addFileVersions($file, $fileVersionData, $versionCounter);
                }
            }

            $file->setVersion($versionCounter);

            $file->setMedia($media);

            $em->persist($file);
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

                // file Version update
                /**
                 * @var FileVersion $file
                 */
                $file = $this->getRequest()->get('file');
                if (isset($file['id'])) {
                    $fileId = $file['id'];
                    $file = $media->getFiles()[$fileId];
                    $versionCounter = $file->getVersion();
                } else {
                    $file = new File();
                    $file->setCreated(new DateTime());
                    $file->setChanged(new DateTime());
                    $file->setCreator($this->getUser());
                    $file->setChanger($this->getUser());
                    $versionCounter = 0;
                }

                if (!empty($_FILES['fileVersion'])) {
                    foreach ($this->getUploadedFiles('fileVersion') as $fileVersionData) {
                        $this->addFileVersions($file, $fileVersionData, $versionCounter);
                    }
                }

                $file->setVersion($versionCounter);

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

        if (isset($metaData['id'])) {
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

    /**
     * get uploaded file when name is 'file' or 'file[]'
     * @param $name
     * @return array
     */
    private function getUploadedFiles($name)
    {
        $files = array();

        if (!is_array($_FILES[$name]['tmp_name'])) {
            // Is single file
            array_push($files, $_FILES[$name]);
        } else {
            // are multiple files
            $fileCount = count($_FILES[$name]['tmp_name']);

            for ($i = 1; $i <= $fileCount; $i++) {
                $file = array(
                    'name' => $_FILES[$name]['name'][$i],
                    'type' => $_FILES[$name]['type'][$i],
                    'tmp_name' => $_FILES[$name]['tmp_name'][$i],
                    'error' => $_FILES[$name]['error'][$i],
                    'size' => $_FILES[$name]['size'][$i],
                );
                array_push($files, $file);
            }
        }

        return $files;
    }

    /**
     * get the maximum allowed file size
     * @return int
     */
    private function getMaxFileSize()
    {
        $configMaxFileSize = '16MB'; // TODO get from config
        $maxFileSizeParts = preg_split('/\D/', $configMaxFileSize);
        $digitalUnit = isset($maxFileSizeParts[1]) ? $maxFileSizeParts[1] : 'B';
        $maxFileSize = intval($maxFileSizeParts[0]) * (defined('self::' . $digitalUnit)) ? constant('self::' . $digitalUnit) : self::B;

        return intval($maxFileSize);
    }

    /**
     * get all blocked file types
     * @return array
     */
    private function getBlockedFileTypes()
    {
        $blockedFileTypes = array(); // TODO get from config
        return $blockedFileTypes;
    }

    /**
     * Validate uploaded File
     * @param $fileData
     * @param bool $bool
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\RestException
     */
    protected function validateFile($fileData, $bool = false)
    {
        $valid = true;
        $maxFileSize = $this->getMaxFileSize();

        $blockedFileTypes = $this->getBlockedFileTypes();

        // validate if file was sent
        if (!isset($fileData['tmp_name'])) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('Uploaded file not found', self::EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND);
            }
        }

        // validate if file upload has an error
        if ($fileData['error'] > 0) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('Error in file upload', self::EXCEPTION_CODE_UPLOAD_ERROR);
            }
        }

        // validate the file size
        if ($fileData['size'] >= $maxFileSize) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('Maximum file size is "' . ($maxFileSize / 1024) . '" kb', self::EXCEPTION_CODE_MAX_FILE_SIZE);
            }
        }

        // validate file type
        if (in_array($fileData['type'], $blockedFileTypes)) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('File type "' . $fileData['type'] . '" is blocked', self::EXCEPTION_CODE_BLOCKED_FILE_TYPE);
            }
        }

        return $valid;
    }

    /**
     * move uploaded file in sulu media folder
     * @param $fileData
     * @param $fileId
     * @return array
     */
    private function moveUploadedFile($fileData, $fileId = null)
    {
        $uploadedFolder = $this->getUploadFolder($fileId);
        $fileName = $this->getUniqueFileName($uploadedFolder, $fileData['name']);
        $filePath = $uploadedFolder . $fileName;
        move_uploaded_file($fileData['tmp_name'], $filePath);

        return array($uploadedFolder, $fileName);
    }

    /**
     * get upload folder
     * @param null $fileId
     * @return string
     */
    private function getUploadFolder($fileId = null)
    {
        $segmenting = 10; // TODO get from config
        $folder = '/uploads/sulumedia/'; // TODO get from config
        $segmentId = $fileId;
        if ($segmentId == null) {
            $segmentId = rand(0, $segmenting);
        }

        if ($segmenting > 0) {
            $segmentingPath = sprintf('%02d', ($segmentId % $segmenting + 1)) . '/';

            $folder .= $segmentingPath;
        }

        return $folder;
    }

    /**
     * get recursive a filename that don't exists
     * @param $folder
     * @param $fileName
     * @param int $counter
     * @return string
     */
    protected function getUniqueFileName($folder, $fileName, $counter = 0)
    {
        $newFileName = $fileName;

        if ($counter > 0) {
            $fileNameParts = explode('.', $fileName, 2);
            $newFileName = $fileNameParts[0] . '-' . $counter . '.' . $fileNameParts[1];
        }

        $filePath = $folder . $newFileName;

        if (!file_exists($filePath)) {
            return $newFileName;
        }

        $counter++;
        return $this->getUniqueFileName($folder, $fileName, $counter);
    }

    /**
     * add FileVersion to a file
     * @param File $file
     * @param $fileData
     * @param $versionCounter
     */
    private function addFileVersions(File $file, $fileData, &$versionCounter)
    {
        $em = $this->getDoctrine()->getManager();

        $this->validateFile($fileData);

        // set fileVersion
        $fileVersion = new FileVersion();
        $fileVersion->setCreated(new DateTime());
        $fileVersion->setChanged(new DateTime());
        $fileVersion->setCreator($this->getUser());
        $fileVersion->setChanger($this->getUser());
        $fileVersion->setVersion(1);

        $fileVersion->setSize($fileData['size']);

        // set Tempory Name Temporary
        $fileVersion->setName($fileData['tmp_name']);
        $fileVersion->setPath(sys_get_temp_dir());

        $fileVersion->setFile($file);
        $versionCounter++;
        $fileVersion->setVersion($versionCounter);
        $file->addFileVersion($fileVersion);

        $fileVersionContentLanguageDatas = $this->getRequest()->get('contentLanguages');
        if (!empty($fileVersionContentLanguageDatas)) {
            foreach ($fileVersionContentLanguageDatas as $fileVersionContentLanguageData) {
                $this->addFileVersionContentLanguages($fileVersion, $fileVersionContentLanguageData);
            }
        }

        $fileVersionPublishLanguageDatas = $this->getRequest()->get('publishLanguages');
        if (!empty($fileVersionPublishLanguageDatas)) {
            foreach ($fileVersionPublishLanguageDatas as $fileVersionPublishLanguageData) {
                $this->addFileVersionPublishLanguages($fileVersion, $fileVersionPublishLanguageData);
            }
        }

        $em->persist($fileVersion);
        $em->flush();

        list($path, $name) = $this->moveUploadedFile($fileData, $fileVersion->getId());

        $fileVersion->setName($name);
        $fileVersion->setPath($path);

        $em->persist($fileVersion);
        $em->flush();

        $file->addFileVersion($fileVersion);
    }

    /**
     * add content languages to fileversion
     * @param FileVersion $fileVersion
     * @param $fileVersionContentLanguageData
     */
    private function addFileVersionContentLanguages (FileVersion $fileVersion, $fileVersionContentLanguageData)
    {
        $em = $this->getDoctrine()->getManager();

        $fileVersionContentLanguages = new FileVersionContentLanguage();
        $fileVersionContentLanguages->setLocale($fileVersionContentLanguageData['locale']);
        $fileVersionContentLanguages->setFileVersion($fileVersion);

        $em->persist($fileVersionContentLanguages);
        $fileVersion->addFileVersionContentLanguage($fileVersionContentLanguages);
    }

    /**
     * add publish languages to fileversion
     * @param FileVersion $fileVersion
     * @param $fileVersionPublishLanguageData
     */
    private function addFileVersionPublishLanguages (FileVersion $fileVersion, $fileVersionPublishLanguageData)
    {
        $em = $this->getDoctrine()->getManager();

        $fileVersionPublishLanguages = new FileVersionPublishLanguage();
        $fileVersionPublishLanguages->setLocale($fileVersionPublishLanguageData['locale']);
        $fileVersionPublishLanguages->setFileVersion($fileVersion);

        $em->persist($fileVersionPublishLanguages);
        $fileVersion->addFileVersionPublishLanguage($fileVersionPublishLanguages);
    }
}
