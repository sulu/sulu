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
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package Sulu\Bundle\MediaBundle\Media\FormatManager
 */
class DefaultFormatManager implements FormatManagerInterface
{
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
     * @var string
     */
    private $ghostScriptPath;

    /**
     * @var bool
     */
    private $saveImage = false;

    /**
     * @param MediaRepository $mediaRepository
     * @param StorageInterface $originalStorage
     * @param FormatCacheInterface $formatCache
     * @param ImageConverterInterface $converter
     * @param $ghostScriptPath
     * @param $saveImage
     */
    public function __construct(
        MediaRepository $mediaRepository,
        StorageInterface $originalStorage,
        FormatCacheInterface $formatCache,
        ImageConverterInterface $converter,
        $ghostScriptPath,
        $saveImage
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->originalStorage = $originalStorage;
        $this->formatCache = $formatCache;
        $this->converter = $converter;
        $this->ghostScriptPath = $ghostScriptPath;
        $this->saveImage = $saveImage == 'true' ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function returnImage($id, $format)
    {
        // load Media
        $media = $this->mediaRepository->findMediaById($id);

        if (!$media) {
            throw new ImageProxyMediaNotFoundException('Media was not found');
        }

        // load Media Data
        list($fileName, $version, $storageOptions) = $this->getMediaData($media);

        // load Original
        $uri = $this->originalStorage->load($fileName, $version, $storageOptions);
        $original = $this->createTmpFile($this->getFile($uri));

        // prepare Media
        $this->prepareMedia($fileName, $original);

        // convert Media to format
        $image = $this->converter->convert($original, $format);

        // set extension
        $imageExtension = $this->getFileExtension($fileName);
        $image = $image->get($imageExtension);

        // set header
        $headers = array(
            'Content-Type' => 'image/' . $imageExtension
        );

        // save image
        if ($this->saveImage) {
            $this->formatCache->save(
                $this->createTmpFile($image),
                $media->getId(),
                $this->replaceExtension($fileName, $this->getFileExtension($fileName)),
                $storageOptions,
                $format
            );
        }

        // return image
        return new Response($image, 200, $headers);
    }

    /**
     * @param string $fileName
     * @param string $path
     */
    protected function prepareMedia($fileName, $path)
    {
        $pathInfos = pathinfo($fileName);
        if (isset($pathInfos['extension'])) {
            if ('pdf' == $pathInfos['extension']) {
                $this->convertPdfToImage($path);
            }
        }
    }

    /**
     * @param string  $path
     */
    protected function convertPdfToImage($path)
    {
        $command = $this->ghostScriptPath . ' -dNOPAUSE -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -sOutputFile=' . $path . ' -dJPEGQ=100 -r300x300 -q ' . $path . ' -c quit';
        exec($command);
    }

    /**
     * @param $filename
     * @param $new_extension
     * @return string
     */
    protected function replaceExtension($filename, $new_extension) {
        $info = pathinfo($filename);
        return $info['filename'] . '.' . $new_extension;
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function getFileExtension($fileName)
    {
        $extension = null;
        $pathInfo = pathinfo($fileName);
        if (isset($pathInfo['extension'])) {
            $extension = $pathInfo['extension'];
        }

        switch ($extension) {
            case 'png':
            case 'gif':
                // do nothing
                break;
            default:
                $extension = 'jpg';
                break;
        }

        return $extension;
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
     * @param string $uri
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
     * @param Media $media
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
     * {@inheritdoc}
     */
    public function getFormats($id, $fileName, $storageOptions)
    {
        $formats = array();

        foreach ($this->converter->getFormats() as $format) {
            $formats[$format['name']] = $this->formatCache->getMediaUrl(
                $id,
                $this->replaceExtension($fileName, $this->getFileExtension($fileName)),
                $storageOptions,
                $format['name']
            );
        }

        return $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($idMedia, $fileName, $options)
    {
        return $this->formatCache->purge($idMedia, $fileName, $options);
    }
} 
