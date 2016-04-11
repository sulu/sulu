<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use Imagick;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Imagine;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\GhostScriptNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidImageFormat;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\Exception\OriginalFileNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailServiceInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sulu format manager for media.
 */
class FormatManager implements FormatManagerInterface
{
    /**
     * The repository for communication with the database.
     *
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
     * @var array
     */
    private $previewMimeTypes = [];

    /**
     * @var array
     */
    private $responseHeaders = [];

    /**
     * @var array
     */
    private $tempFiles = [];

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var array
     */
    private $formats;

    /**
     * @var VideoThumbnailServiceInterface
     */
    private $videoThumbnailService;

    /**
     * @param MediaRepository         $mediaRepository
     * @param StorageInterface        $originalStorage
     * @param FormatCacheInterface    $formatCache
     * @param ImageConverterInterface $converter
     * @param VideoThumbnailServiceInterface $videoThumbnailService
     * @param string                  $ghostScriptPath
     * @param string                  $saveImage
     * @param array                   $previewMimeTypes
     * @param array                   $responseHeaders
     * @param array                   $formats
     */
    public function __construct(
        MediaRepository $mediaRepository,
        StorageInterface $originalStorage,
        FormatCacheInterface $formatCache,
        ImageConverterInterface $converter,
        VideoThumbnailServiceInterface $videoThumbnailService,
        $ghostScriptPath,
        $saveImage,
        $previewMimeTypes,
        $responseHeaders,
        $formats
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->originalStorage = $originalStorage;
        $this->formatCache = $formatCache;
        $this->converter = $converter;
        $this->ghostScriptPath = $ghostScriptPath;
        $this->saveImage = $saveImage == 'true' ? true : false;
        $this->previewMimeTypes = $previewMimeTypes;
        $this->responseHeaders = $responseHeaders;
        $this->fileSystem = new Filesystem();
        $this->formats = $formats;
        $this->videoThumbnailService = $videoThumbnailService;
    }

    /**
     * {@inheritdoc}
     */
    public function returnImage($id, $formatName)
    {
        $setExpireHeaders = false;

        try {
            // load Media
            $media = $this->mediaRepository->findMediaById($id);

            if (!$media) {
                throw new ImageProxyMediaNotFoundException('Media was not found');
            }

            // load Media Data
            list($fileName, $version, $storageOptions, $mimeType) = $this->getMediaData($media);

            try {
                // check if file has supported preview
                if (!$this->checkPreviewSupported($mimeType)) {
                    throw new InvalidMimeTypeForPreviewException($mimeType);
                }

                // get format options
                $format = $this->getFormat($formatName);
                $formatOptions = $format['options'];

                // load Original
                $uri = $this->originalStorage->load($fileName, $version, $storageOptions);
                $original = $this->createTmpFile($this->getFile($uri, $mimeType));

                // prepare Media
                $this->prepareMedia($mimeType, $original);

                // convert Media to format
                $image = $this->converter->convert($original, $format);

                // remove profiles and comments
                $image->strip();

                // set Interlacing to plane for smaller image size
                if (count($image->layers()) == 1) {
                    $image->interlace(ImageInterface::INTERLACE_PLANE);
                }

                // set extension
                $imageExtension = $this->getImageExtension($fileName);

                // get image
                $responseContent = $image->get(
                    $imageExtension,
                    $this->getOptionsFromImage($image, $imageExtension, $formatOptions)
                );

                // HTTP Headers
                $status = 200;
                $setExpireHeaders = true;

                // save image
                if ($this->saveImage) {
                    $this->formatCache->save(
                        $this->createTmpFile($responseContent),
                        $media->getId(),
                        $this->replaceExtension($fileName, $imageExtension),
                        $storageOptions,
                        $formatName
                    );
                }
            } catch (MediaException $e) {
                // return when available a file extension icon
                list($responseContent, $status, $imageExtension) = $this->returnFileExtensionIcon($formatName, $this->getRealFileExtension($fileName), $e);
            }
            $responseMimeType = 'image/' . $imageExtension;
        } catch (MediaException $e) {
            $responseContent = $e->getCode() . ': ' . $e->getMessage();
            $status = 404;
            $responseMimeType = 'text/plain';
        }

        // clear temp files
        $this->clearTempFiles();

        // set header
        $headers = $this->getResponseHeaders($responseMimeType, $setExpireHeaders);

        // return image
        return new Response($responseContent, $status, $headers);
    }

    /**
     * return the options for the given format.
     *
     * @param $format
     *
     * @return array
     *
     * @throws ImageProxyInvalidImageFormat
     */
    protected function getFormat($format)
    {
        if (!isset($this->formats[$format])) {
            throw new ImageProxyInvalidImageFormat('Format was not found');
        }

        return $this->formats[$format];
    }

    /**
     * @param $format
     * @param $fileExtension
     * @param MediaException $e
     *
     * @return array
     *
     * @throws ImageProxyInvalidImageFormat
     * @throws MediaException
     */
    protected function returnFileExtensionIcon($format, $fileExtension, $e)
    {
        $imageExtension = 'png';

        $placeholder = dirname(__FILE__) . '/../../Resources/images/file-' . $fileExtension . '.png';

        if (!file_exists(dirname(__FILE__) . '/../../Resources/images/file-' . $fileExtension . '.png')) {
            throw $e;
        }

        $image = $this->converter->convert($placeholder, $this->getFormat($format));

        $image = $image->get($imageExtension);

        return [$image, 200, $imageExtension];
    }

    /**
     * @param string $mimeType
     * @param bool $setExpireHeaders
     *
     * @return array
     */
    protected function getResponseHeaders($mimeType = '', $setExpireHeaders = false)
    {
        $headers = [];

        if (!empty($mimeType)) {
            $headers['Content-Type'] = $mimeType;
        }

        if (empty($this->responseHeaders)) {
            return $headers;
        }

        $headers = array_merge(
            $headers,
            $this->responseHeaders
        );

        if (isset($this->responseHeaders['Expires']) && $setExpireHeaders) {
            $date = new \DateTime();
            $date->modify($this->responseHeaders['Expires']);
            $headers['Expires'] = $date->format('D, d M Y H:i:s \G\M\T');
        } else {
            // will remove exist set expire header
            $headers['Expires'] = null;
            $headers['Cache-Control'] = 'no-cache';
            $headers['Pragma'] = null;
        }

        return $headers;
    }

    /**
     * @param ImageInterface $image
     * @param string         $imageExtension
     * @param array          $formatOptions
     *
     * @return array
     */
    protected function getOptionsFromImage(ImageInterface $image, $imageExtension, $formatOptions)
    {
        $options = [];
        if (count($image->layers()) > 1 && $imageExtension == 'gif') {
            $options['animated'] = true;
        }

        return array_merge($options, $formatOptions);
    }

    /**
     * @param string $mimeType
     * @param string $path
     */
    protected function prepareMedia($mimeType, $path)
    {
        switch ($mimeType) {
            case 'application/pdf':
                $this->convertPdfToImage($path);
                break;
            case 'image/vnd.adobe.photoshop':
                $this->convertPsdToImage($path);
                break;
        }
    }

    /**
     * @param string $path
     *
     * @throws GhostScriptNotFoundException
     */
    protected function convertPdfToImage($path)
    {
        $command = $this->ghostScriptPath .
            ' -dNOPAUSE -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -sOutputFile=' .
            $path .
            ' -dJPEGQ=100 -r300x300 -q ' .
            $path .
            ' -c quit';

        exec($command);

        $file = new SymfonyFile($path);

        if ($file->getMimeType() == 'application/pdf') {
            throw new GhostScriptNotFoundException(
                'Ghostscript was not found at "' .
                $this->ghostScriptPath .
                '" or user has no Permission for "' .
                $path .
                '"'
            );
        }
    }

    /**
     * @param $path
     *
     * @throws MediaException
     */
    protected function convertPsdToImage($path)
    {
        if (class_exists('Imagick')) {
            $imagine = new Imagine();
            $image = $imagine->open($path);
            $image = $image->layers()[0];
            file_put_contents($path, $image->get('png'));
        } else {
            throw new InvalidMimeTypeForPreviewException('image/vnd.adobe.photoshop');
        }
    }

    /**
     * @param string $filename
     * @param string $newExtension
     *
     * @return string
     */
    protected function replaceExtension($filename, $newExtension)
    {
        $info = pathinfo($filename);

        return $info['filename'] . '.' . $newExtension;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getImageExtension($fileName)
    {
        $extension = $this->getRealFileExtension($fileName);

        switch ($extension) {
            case 'png':
            case 'gif':
                // do nothing
                break;
            case 'svg':
                $extension = 'png';
                break;
            default:
                $extension = 'jpg';
                break;
        }

        return $extension;
    }

    /**
     * @param $fileName
     */
    protected function getRealFileExtension($fileName)
    {
        $pathInfo = pathinfo($fileName);
        if (isset($pathInfo['extension'])) {
            return $pathInfo['extension'];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaProperties($url)
    {
        return $this->formatCache->analyzedMediaUrl($url);
    }

    /**
     * get file from namespace.
     *
     * @param string $uri
     * @param string $mimeType
     *
     * @return string
     *
     * @throws OriginalFileNotFoundException
     */
    protected function getFile($uri, $mimeType)
    {
        if (fnmatch('video/*', $mimeType)) {
            $tempFile = tempnam(sys_get_temp_dir(), 'media_original') . '.jpg';
            $this->videoThumbnailService->generate($uri, '00:00:02:01', $tempFile);
            $uri = $tempFile;
        }

        $file = @file_get_contents($uri);

        if (!$file) {
            throw new OriginalFileNotFoundException($uri);
        }

        return $file;
    }

    /**
     * create a local temp file for the original.
     *
     * @param $content
     *
     * @return string
     */
    protected function createTmpFile($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'media_original');
        $handle = fopen($tempFile, 'w');
        fwrite($handle, $content);
        fclose($handle);

        $this->tempFiles[] = $tempFile;

        return $tempFile;
    }

    /**
     * delete all created temp files.
     *
     * @return $this
     */
    protected function clearTempFiles()
    {
        $this->fileSystem->remove($this->tempFiles);

        return $this;
    }

    /**
     * @param Media $media
     *
     * @return array
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException
     */
    protected function getMediaData($media)
    {
        $fileName = null;
        $storageOptions = null;
        $version = null;
        $mimeType = null;

        /** @var File $file */
        foreach ($media->getFiles() as $file) {
            $version = $file->getVersion();
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    $fileName = $fileVersion->getName();
                    $storageOptions = $fileVersion->getStorageOptions();
                    $mimeType = $fileVersion->getMimeType();
                    break;
                }
            }
            break;
        }

        if (!$fileName) {
            throw new ImageProxyMediaNotFoundException('Media file version was not found');
        }

        return [$fileName, $version, $storageOptions, $mimeType];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats($id, $fileName, $storageOptions, $version, $mimeType)
    {
        $formats = [];
        if ($this->checkPreviewSupported($mimeType)) {
            foreach ($this->formats as $format) {
                $formats[$format['name']] = $this->formatCache->getMediaUrl(
                    $id,
                    $this->replaceExtension($fileName, $this->getImageExtension($fileName)),
                    $storageOptions,
                    $format['name'],
                    $version
                );
            }
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

    /**
     * Clears the format cache.
     */
    public function clearCache()
    {
        $this->formatCache->clear();
    }

    /**
     * @param $mimeType
     *
     * @return bool
     */
    private function checkPreviewSupported($mimeType)
    {
        foreach ($this->previewMimeTypes as $type) {
            if (fnmatch($type, $mimeType)) {
                return true;
            }
        }

        return false;
    }
}
