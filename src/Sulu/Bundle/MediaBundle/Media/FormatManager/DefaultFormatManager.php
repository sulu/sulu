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

use Imagine\Image\ImageInterface;
use Imagine\Imagick\Imagine;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidExtensionForPreviewException;
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
    private $previewExtensions = array();

    /**
     * @param MediaRepository $mediaRepository
     * @param StorageInterface $originalStorage
     * @param FormatCacheInterface $formatCache
     * @param ImageConverterInterface $converter
     * @param string $ghostScriptPath
     * @param string $saveImage
     * @param array $previewExtensions
     */
    public function __construct(
        MediaRepository $mediaRepository,
        StorageInterface $originalStorage,
        FormatCacheInterface $formatCache,
        ImageConverterInterface $converter,
        $ghostScriptPath,
        $saveImage,
        $previewExtensions
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->originalStorage = $originalStorage;
        $this->formatCache = $formatCache;
        $this->converter = $converter;
        $this->ghostScriptPath = $ghostScriptPath;
        $this->saveImage = $saveImage == 'true' ? true : false;
        $this->previewExtensions = $previewExtensions;
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
            list($fileName, $version, $storageOptions) = $this->getMediaData($media);

            try {
                // check if file has supported preview
                $extension = $this->getRealFileExtension($fileName);
                if (!in_array($extension, $this->previewExtensions)) {
                    throw new InvalidExtensionForPreviewException($extension);
                }

                // load Original
                $uri = $this->originalStorage->load($fileName, $version, $storageOptions);
                $original = $this->createTmpFile($this->getFile($uri));

                // prepare Media
                $this->prepareMedia($fileName, $original);

                // set extension
                $imageExtension = $this->getImageExtension($fileName);

                // convert Media to format
                $image = $this->converter->convert($original, $format);

                $image->strip();
                $image->interlace(ImageInterface::INTERLACE_PLANE);

                // get image
                $image = $image->get($imageExtension, $this->getOptionsFromImage($image));

                // set header
                $headers = array(
                    'Content-Type' => 'image/' . $imageExtension
                );

                // save image
                if ($this->saveImage) {
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
                return $this->returnFileExtensionIcon($format, $this->getRealFileExtension($fileName));
            }
        } catch (MediaException $e) {
            // return default image
            return $this->returnFallbackImage($format);
        }

        // return image
        return new Response($image, 200, $headers);
    }

    /**
     * @param string $format
     * @param string $fileExtension
     * @return Response
     */
    protected function returnFileExtensionIcon($format, $fileExtension)
    {
        $imageExtension = 'png';

        $headers = array(
            'Content-Type' => 'image/' . $imageExtension
        );

        $placeholder = dirname(__FILE__) . '/../../Resources/images/file-'.$fileExtension.'.png';

        if (!file_exists(dirname(__FILE__) . '/../../Resources/images/file-'.$fileExtension.'.png')) {
            return $this->returnFallbackImage($format);
        }

        $image = $this->converter->convert($placeholder, $format);

        $image = $image->get($imageExtension);

        return new Response($image, 200, $headers);
    }

    /**
     * @param string $format
     * @return Response
     */
    protected function returnFallbackImage($format)
    {
        $imageExtension = 'png';

        $headers = array(
            'Content-Type' => 'image/' . $imageExtension
        );

        $placeholder = dirname(__FILE__) . '/../../Resources/images/placeholder.png';

        $image = $this->converter->convert($placeholder, $format);

        $image = $image->get($imageExtension);

        return new Response($image, 404, $headers);
    }

    /**
     * @param ImageInterface $image
     * @return array
     */
    protected function getOptionsFromImage(ImageInterface $image)
    {
        $options = array();
        if (count($image->layers()) > 1) {
            $options['animated'] = true;
        }

        return $options;
    }

    /**
     * @param string $fileName
     * @param string $path
     */
    protected function prepareMedia($fileName, $path)
    {
        switch ($this->getRealFileExtension($fileName)) {
            case 'pdf':
                $this->convertPdfToImage($path);
                break;
            case 'psd':
                $this->convertPsdToImage($path);
                break;
        }
    }

    /**
     * @param string $path
     */
    protected function convertPdfToImage($path)
    {
        $command = $this->ghostScriptPath . ' -dNOPAUSE -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -sOutputFile=' . $path . ' -dJPEGQ=100 -r300x300 -q ' . $path . ' -c quit';
        exec($command);
    }

    /**
     * @param $path
     */
    protected function convertPsdToImage($path)
    {
        if (class_exists('Imagick')) {
            $imagine = new Imagine();
            $image = $imagine->open($path);
            $image = $image->layers()[0];
            file_put_contents($path, $image->get('png'));
        }
    }

    /**
     * @param string $filename
     * @param string $newExtension
     * @return string
     */
    protected function replaceExtension($filename, $newExtension) {
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

        /** @var File $file */
        foreach ($media->getFiles() as $file) {
            $version = $file->getVersion();
            /** @var FileVersion $fileVersion */
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
                $this->replaceExtension($fileName, $this->getImageExtension($fileName)),
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
