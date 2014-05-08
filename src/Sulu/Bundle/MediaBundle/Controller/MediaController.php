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
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use \DateTime;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
     * @var string
     */
    protected $entityNameCollection = 'SuluMediaBundle:Collection';

    /**
     * @var string
     */
    protected $entityNameMediaType = 'SuluMediaBundle:MediaType';

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
                    ->getRepository($this->entityNameCollection)
                    ->find($collectionData['id']);
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

            $mediaTypeId = $this->getMediaType('fileVersion');
            $mediaType = $this->getDoctrine()
                ->getRepository($this->entityNameMediaType)
                ->find($mediaTypeId);
            if (!$mediaType) {
                throw new EntityNotFoundException('SuluMediaBundle:MediaType', $mediaTypeId);
            }

            $media->setType($mediaType);

            // set file
            $file = new File();
            $file->setCreated(new DateTime());
            $file->setChanged(new DateTime());
            $file->setCreator($this->getUser());
            $file->setChanger($this->getUser());
            $file->setVersion(0);
            $file->setMedia($media);

            $em->persist($file);
            $em->persist($media);

            // set fileVersions
            $versionCounter = 0;

            $uploadFiles = $this->getUploadedFiles('fileVersion');
            if (count($uploadFiles)) {
                foreach ($uploadFiles as $uploadFile) {
                    $this->addFileVersions($file, $uploadFile, $versionCounter);
                }
            } else {
                throw new RestException('Uploaded file not found', self::EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND);
            }

            $file->setVersion($versionCounter);

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
     * get media type from post or set it from file mimetype
     * @param $fileName
     * @return mixed
     */
    protected function getMediaType($fileName)
    {
        $mediaTypeData = $this->getRequest()->get('type');
        if (!is_null($mediaTypeData) && isset($mediaTypeData['id'])) {
            return $mediaTypeData['id'];
        }

        $imageFileTypes = array(); // TODO from config
        $videoFileTypes = array(); // TODO from config

        $mediaTypeId = MediaType::TYPE_DEFAULT;

        /**
         * @var UploadedFile $uploadFile
         */
        foreach ($this->getUploadedFiles($fileName) as $uploadFile)
        {
            if (in_array($uploadFile->getMimeType(), $imageFileTypes)) {
                $mediaTypeId = MediaType::TYPE_IMAGE;
            } elseif (in_array($uploadFile->getMimeType(), $videoFileTypes)) {
                $mediaTypeId = MediaType::TYPE_VIDEO;
            } else {
                $mediaTypeId = MediaType::TYPE_DEFAULT;
            }
            break;
        }
        return $mediaTypeId;
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
     * @param FileVersion $fileVersion The media on which is worked
     * @return bool True if the processing was sucessful, otherwise false
     */
    protected function processMetas(FileVersion $fileVersion)
    {
        $metas = $this->getRequest()->get('metas');

        $delete = function ($meta) use ($fileVersion) {
            $fileVersion->removeMeta($meta);

            return true;
        };

        $update = function ($meta, $matchedEntry) {
            return $this->updateMeta($meta, $matchedEntry);
        };

        $add = function ($meta) use ($fileVersion) {
            $this->addMetas($fileVersion, $meta);

            return true;
        };

        return $this->processPut($fileVersion->getMetas(), $metas, $delete, $update, $add);
    }

    /**
     * Adds META to a media
     * @param FileVersion $fileVersion
     * @param $metaData
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addMetas(FileVersion $fileVersion, $metaData)
    {
        $em = $this->getDoctrine()->getManager();
        $metaEntity = 'SuluMediaBundle:FileVersionMeta';

        if (isset($metaData['id'])) {
            throw new EntityIdAlreadySetException($metaEntity, $metaData['id']);
        } else {
            $meta = new FileVersionMeta();
            $meta->setMedia($fileVersion);
            $meta->setTitle($metaData['title']);
            $meta->setDescription($metaData['description']);
            $meta->setLocale($metaData['locale']);

            $em->persist($meta);
            $fileVersion->addMeta($meta);
        }
    }

    /**
     * Updates the given meta
     * @param FileVersionMeta $meta The fileversion meta object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function updateMeta(FileVersionMeta $meta, $entry)
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
        if (is_null($this->getRequest()->files->get($name))) {
            return array();
        }

        if (is_array($this->getRequest()->files->get($name))) {
            return $this->getRequest()->files->get($name);
        }

        return array(
            $this->getRequest()->files->get($name)
        );
    }

    /**
     * get the maximum allowed file size
     * @return int
     */
    private function getMaxFileSize()
    {
        $configMaxFileSize = $this->container->getParameter('sulu_media.media.max_file_size');
        $value = intval($configMaxFileSize);
        $maxFileSizeParts = preg_split('/\d+/', $configMaxFileSize);
        $digitalUnit = isset($maxFileSizeParts[1]) ? $maxFileSizeParts[1] : 'B';

        $unitInBytes = (defined('self::' . $digitalUnit)) ? constant('self::' . $digitalUnit) : self::B;

        $maxFileSize = intval($value) * $unitInBytes;


        return intval($maxFileSize);
    }

    /**
     * get all blocked file types
     * @return array
     */
    private function getBlockedFileTypes()
    {
        $blockedFileTypes = $this->container->getParameter('sulu_media.media.blocked_file_types');
        return $blockedFileTypes;
    }

    /**
     * Validate uploaded File
     * @param UploadedFile $uploadFile
     * @param bool $bool
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\RestException
     */
    protected function validateFile(UploadedFile $uploadFile, $bool = false)
    {
        $valid = true;
        $maxFileSize = $this->getMaxFileSize();

        $blockedFileTypes = $this->getBlockedFileTypes();

        // validate if file was sent
        if (!$uploadFile instanceof UploadedFile) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('Uploaded file not found', self::EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND);
            }
        }

        // validate if file upload has an error
        if ($uploadFile->getError() > 0) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('Error in file upload', self::EXCEPTION_CODE_UPLOAD_ERROR);
            }
        }

        // validate the file size
        if ($uploadFile->getSize() >= $maxFileSize) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('Maximum file size is "' . ($maxFileSize / 1024) . '" kb', self::EXCEPTION_CODE_MAX_FILE_SIZE);
            }
        }

        // validate file type
        if (in_array($uploadFile->getMimeType(), $blockedFileTypes)) {
            if ($bool) {
                $valid = false;
            } else {
                throw new RestException('File type "' . $uploadFile->getMimeType() . '" is blocked', self::EXCEPTION_CODE_BLOCKED_FILE_TYPE);
            }
        }

        return $valid;
    }

    /**
     * move uploaded file in sulu media folder
     * @param UploadedFile $uploadFile
     * @param $fileId
     * @return array
     */
    private function moveUploadedFile(UploadedFile $uploadFile, $fileId = null)
    {
        $uploadedFolder = $this->getUploadFolder($fileId);
        $fileName = $this->getUniqueFileName($uploadedFolder, $uploadFile->getFilename());
        $uploadFile->move($uploadedFolder, $fileName);

        return array($uploadedFolder, $fileName);
    }

    /**
     * get upload folder
     * @param null $fileId
     * @return string
     */
    private function getUploadFolder($fileId = null)
    {
        $segmenting = $this->container->getParameter('sulu_media.media.folder.segments');
        $folder = $this->container->getParameter('sulu_media.media.folder.path');
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
     * @param File $file
     * @param UploadedFile $uploadFile
     * @param $versionCounter
     * @throws \Sulu\Component\Rest\Exception\RestException
     */
    private function addFileVersions(File &$file, UploadedFile $uploadFile, &$versionCounter)
    {
        $em = $this->getDoctrine()->getManager();

        $this->validateFile($uploadFile);

        // set fileVersion
        $fileVersion = new FileVersion();
        $fileVersion->setCreated(new DateTime());
        $fileVersion->setChanged(new DateTime());
        $fileVersion->setCreator($this->getUser());
        $fileVersion->setChanger($this->getUser());

        $fileVersion->setSize($uploadFile->getSize());

        // set Tempory Name Temporary
        $fileVersion->setName($uploadFile->getFilename());
        $fileVersion->setPath($uploadFile->getPath());

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

        // set metas
        if (!$this->processMetas($fileVersion)) {
            throw new RestException('Updating dependencies is not possible', 0);
        }

        $em->persist($fileVersion);
        $em->flush();

        list($path, $name) = $this->moveUploadedFile($uploadFile, $fileVersion->getId());

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
