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

use Faker\Provider\cs_CZ\DateTime;
use Imagick;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Imagine;
use SebastianBergmann\Exporter\Exception;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\GhostScriptNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
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
     * @var array
     */
    private $previewMimeTypes = array();

    /**
     * @var string
     */
    private $cacheTime = '';

    /**
     * @param MediaRepository $mediaRepository
     * @param StorageInterface $originalStorage
     * @param FormatCacheInterface $formatCache
     * @param ImageConverterInterface $converter
     * @param string $ghostScriptPath
     * @param string $saveImage
     * @param array $previewMimeTypes
     * @param array $responseHeaders
     */
    public function __construct(
        MediaRepository $mediaRepository,
        StorageInterface $originalStorage,
        FormatCacheInterface $formatCache,
        ImageConverterInterface $converter,
        $ghostScriptPath,
        $saveImage,
        $previewMimeTypes,
        $responseHeaders
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->originalStorage = $originalStorage;
        $this->formatCache = $formatCache;
        $this->converter = $converter;
        $this->ghostScriptPath = $ghostScriptPath;
        $this->saveImage = $saveImage == 'true' ? true : false;
        $this->previewMimeTypes = $previewMimeTypes;
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function returnImage($id, $format)
    {
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
                if (!in_array($mimeType, $this->previewMimeTypes)) {
                    throw new InvalidMimeTypeForPreviewException($mimeType);
                }

                // load Original
                $uri = $this->originalStorage->load($fileName, $version, $storageOptions);
                $original = $this->createTmpFile($this->getFile($uri));

                // prepare Media
                $this->prepareMedia($mimeType, $original);

                // convert Media to format
                $image = $this->converter->convert($original, $format);

                // remove profiles and comments
                $image->strip();

                // set Interlacing to plane for smaller image size
                $image->interlace(ImageInterface::INTERLACE_PLANE);

                // set extension
                $imageExtension = $this->getImageExtension($fileName);

                // get image
                $image = $image->get($imageExtension, $this->getOptionsFromImage($image, $imageExtension));

                // HTTP Status
                $status = 200;

                // save image
                if ($this->saveImage && false) {
                    $this->formatCache->save(
                        $this->createTmpFile($image),
                        $media->getId(),
                        $this->replaceExtension($fileName, $imageExtension),
                        $storageOptions,
                        $format
                    );
                }
            } catch (MediaException $e) {
                // return when available a file extension icon
                list($image, $status, $imageExtension) = $this->returnFileExtensionIcon($format, $this->getRealFileExtension($fileName));
            }
        } catch (MediaException $e) {
            // return default image
            list($image, $status, $imageExtension) = $this->returnFallbackImage($format);
        }

        // set header
        $headers = $this->getResponseHeaders($imageExtension);

        // return image
        return new Response($image, $status, $headers);
    }

    /**
     * @param string $format
     * @param string $fileExtension
     * @return Response
     */
    protected function returnFileExtensionIcon($format, $fileExtension)
    {
        $imageExtension = 'png';

        $placeholder = dirname(__FILE__) . '/../../Resources/images/file-' . $fileExtension . '.png';

        if (!file_exists(dirname(__FILE__) . '/../../Resources/images/file-' . $fileExtension . '.png')) {
            return $this->returnFallbackImage($format);
        }

        $image = $this->converter->convert($placeholder, $format);

        $image = $image->get($imageExtension);

        return array($image, 200, $imageExtension);
    }

    /**
     * @param string $format
     * @return Response
     */
    protected function returnFallbackImage($format)
    {
        $imageExtension = 'png';

        $placeholder = dirname(__FILE__) . '/../../Resources/images/placeholder.png';

        $image = $this->converter->convert($placeholder, $format);

        $image = $image->get($imageExtension);

        return array($image, 404, $imageExtension);
    }

    /**
     * @param $imageExtension
     * @return array
     */
    protected function getResponseHeaders($imageExtension = '')
    {
        $headers = array();

        if (!empty($this->responseHeaders)) {
            $headers = $this->responseHeaders;
            if (isset($this->responseHeaders['Expires'])) {
                $date = new \DateTime();
                $date->modify($this->responseHeaders['Expires']);
                $headers['Expires'] = $date->format('D, d M Y H:i:s \G\M\T');
            }
        }

        if (!empty($imageExtension)) {
            $headers['Content-Type'] = 'image/' . $imageExtension;
        }

        return $headers;
    }

    /**
     * @param ImageInterface $image
     * @param string $imageExtension
     * @return array
     */
    protected function getOptionsFromImage(ImageInterface $image, $imageExtension)
    {
        $options = array();
        if (count($image->layers()) > 1 && $imageExtension == 'gif') {
            $options['animated'] = true;
        }

        return $options;
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

        if (mime_content_type($path) == 'application/pdf') {
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
     * @return string
     */
    protected function replaceExtension($filename, $newExtension)
    {
        $info = pathinfo($filename);

        return $info['filename'] . '.' . $newExtension;
    }

    /**
     * @param string $fileName
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
     * @return null
     */
    protected function getRealFileExtension($fileName)
    {
        $pathInfo = pathinfo($fileName);
        if (isset($pathInfo['extension'])) {
            return $pathInfo['extension'];
        }

        return null;
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
        $tempFile = tempnam(null, 'media_original');
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

        return array($fileName, $version, $storageOptions, $mimeType);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats($id, $fileName, $storageOptions, $version)
    {
        $formats = array();

        foreach ($this->converter->getFormats() as $format) {
            $formats[$format['name']] = $this->formatCache->getMediaUrl(
                $id,
                $this->replaceExtension($fileName, $this->getImageExtension($fileName)),
                $storageOptions,
                $format['name'],
                $version
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
