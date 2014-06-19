<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Symfony\Component\HttpFoundation\Response;

class DefaultFormatManager implements FormatManagerInterface {

    /**
     * The repository for communication with the database
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var FormatCacheInterface
     */
    private $formatCache;

    /**
     * @var StorageInterface
     */
    private $originalStorage;

    /**
     * @var ImageConverterInterface
     */
    private $converter;

    /**
     * @var bool
     */
    private $saveImage = false;

    /**
     * @param MediaRepository $mediaRepository
     * @param StorageInterface $originalStorage
     * @param FormatCacheInterface $formatCache
     * @param ImageConverterInterface $converter
     * @param $saveImage
     */
    public function __construct(
        MediaRepository $mediaRepository,
        StorageInterface $originalStorage,
        FormatCacheInterface $formatCache,
        ImageConverterInterface $converter,
        $saveImage
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->originalStorage = $originalStorage;
        $this->formatCache = $formatCache;
        $this->converter = $converter;
        $this->saveImage = $saveImage == 'true' ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function returnImage($id, $format)
    {
        $media = $this->mediaRepository->findMediaById($id);

        if (!$media) {
            throw new ImageProxyMediaNotFoundException('Media was not found');
        }

        $uri = $this->getOriginalByMedia($media);

        $original = $this->createTmpFile($this->getFile($uri));

        $image = $this->converter->convert($original, $format);

        $headers = array(
            'Content-Type' => 'image/jpeg'
        );

        $image = $image->get('jpeg');

        if ($this->saveImage) {
            list($fileName, $version, $storageOptions) = $this->getMediaData($media);
            $this->formatCache->save($this->createTmpFile($image), $media->getId(), $fileName, $storageOptions, $format);
        }

        return new Response($image, 200, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaProperties($url)
    {
        return $this->formatCache->analyzedMediaUrl($url);
    }

    /**
     * get file from namespace
     * @param $uri
     * @return string
     */
    protected function getFile($uri)
    {
        return file_get_contents($uri);
    }

    /**
     * create a local temp file for the original
     * @param $content
     * @return string
     */
    protected function createTmpFile($content)
    {
        $tempFile = tempnam('/tmp', 'media_original');
        $handle = fopen($tempFile, 'w');
        fwrite($handle, $content);
        fclose($handle);

        return $tempFile;
    }

    /**
     * @param $media
     * @return mixed
     * @throws ImageProxyMediaNotFoundException
     */
    protected function getOriginalByMedia($media)
    {
        list($fileName, $version, $storageOptions) = $this->getMediaData($media);

        return $this->originalStorage->load($fileName, $version, $storageOptions);
    }

    /**
     * @param $media
     * @return array
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException
     */
    protected function getMediaData($media)
    {
        $fileName = null;
        $storageOptions = null;
        $version = null;

        /**
         * @var File $file
         */
        foreach ($media->getFiles() as $file) {
            $version = $file->getVersion();
            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    $fileName = $fileVersion->getName();
                    $storageOptions = $fileVersion->getStorageOptions();
                    break;
                }
            }
            break;
        }

        if (!$fileName) {
            throw new ImageProxyMediaNotFoundException('Media file version was not found');
        }

        return array($fileName, $version, $storageOptions);
    }

    /**
     * @param $fileName
     * @param $version
     * @param $storageOptions
     * @return mixed
     */
    public function getOriginal($fileName, $version, $storageOptions)
    {
        return $this->originalStorage->load($fileName, $version, $storageOptions);
    }

    /**
     * @param $id
     * @param $fileName
     * @param $storageOptions
     * @return array
     */
    public function getFormats($id, $fileName, $storageOptions)
    {
        $formats = array();

        foreach ($this->converter->getFormats() as $format) {
            $formats[$format['name']] = $this->formatCache->getMediaUrl($id, $fileName, $storageOptions, $format['name']);
        }

        return $formats;
    }

} 
