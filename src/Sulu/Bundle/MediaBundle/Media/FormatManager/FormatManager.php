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

use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Symfony\Component\Filesystem\Filesystem;
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
     * @var ImageConverterInterface
     */
    private $converter;

    /**
     * @var bool
     */
    private $saveImage = false;

    /**
     * @var array
     */
    private $responseHeaders = [];

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var array
     */
    private $formats;

    /**
     * @var array
     */
    private $supportedMimeTypes;

    /**
     * @param MediaRepositoryInterface $mediaRepository
     * @param FormatCacheInterface $formatCache
     * @param ImageConverterInterface $converter
     * @param string $saveImage
     * @param array $responseHeaders
     * @param array $formats
     * @param array $supportedMimeTypes
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        FormatCacheInterface $formatCache,
        ImageConverterInterface $converter,
        $saveImage,
        $responseHeaders,
        $formats,
        array $supportedMimeTypes
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->formatCache = $formatCache;
        $this->converter = $converter;
        $this->saveImage = $saveImage == 'true' ? true : false;
        $this->responseHeaders = $responseHeaders;
        $this->fileSystem = new Filesystem();
        $this->formats = $formats;
        $this->supportedMimeTypes = $supportedMimeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function returnImage($id, $formatKey)
    {
        $setExpireHeaders = false;

        try {
            $media = $this->mediaRepository->findMediaByIdForRendering($id, $formatKey);

            if (!$media) {
                throw new ImageProxyMediaNotFoundException('Media was not found');
            }

            $fileVersion = $this->getLatestFileVersion($media);

            if (!$this->checkMimeTypeSupported($fileVersion->getMimeType())) {
                throw new InvalidMimeTypeForPreviewException($fileVersion->getMimeType());
            }

            // Convert Media to format.
            $responseContent = $this->converter->convert($fileVersion, $formatKey);

            // HTTP Headers
            $status = 200;
            $setExpireHeaders = true;

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($responseContent);

            // Save image.
            if ($this->saveImage) {
                $this->formatCache->save(
                    $responseContent,
                    $media->getId(),
                    $this->replaceExtension($fileVersion->getName(), $mimeType),
                    $fileVersion->getStorageOptions(),
                    $formatKey
                );
            }
        } catch (MediaException $e) {
            $responseContent = null;
            $status = 404;
            $mimeType = null;
        }

        // Set header.
        $headers = $this->getResponseHeaders($mimeType, $setExpireHeaders);

        // Return image.
        return new Response($responseContent, $status, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats($id, $fileName, $storageOptions, $version, $subVersion, $mimeType)
    {
        $formats = [];
        if ($this->checkMimeTypeSupported($mimeType)) {
            foreach ($this->formats as $format) {
                $formats[$format['key']] = $this->formatCache->getMediaUrl(
                    $id,
                    $this->replaceExtension($fileName, $mimeType),
                    $storageOptions,
                    $format['key'],
                    $version,
                    $subVersion
                );
            }
        }

        return $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($idMedia, $fileName, $mimeType, $options)
    {
        return $this->formatCache->purge($idMedia, $this->replaceExtension($fileName, $mimeType), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaProperties($url)
    {
        return $this->formatCache->analyzedMediaUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache()
    {
        $this->formatCache->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatDefinition($formatKey, $locale = null, array $formatOptions = [])
    {
        if (!isset($this->formats[$formatKey])) {
            throw new FormatNotFoundException($formatKey);
        }

        $format = $this->formats[$formatKey];
        $title = $format['key'];

        if (array_key_exists($locale, $format['meta']['title'])) {
            $title = $format['meta']['title'][$locale];
        } elseif (count($format['meta']['title']) > 0) {
            $title = array_values($format['meta']['title'])[0];
        }

        $formatArray = [
            'key' => $format['key'],
            'title' => $title,
            'scale' => $format['scale'],
            'options' => (!empty($formatOptions)) ? $formatOptions : null,
        ];

        return $formatArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatDefinitions($locale = null, array $formatOptions = [])
    {
        $definitionsArray = [];

        foreach ($this->formats as $format) {
            $options = [];
            if (array_key_exists($format['key'], $formatOptions)) {
                $options = $formatOptions[$format['key']];
            }
            $definitionsArray[$format['key']] = $this->getFormatDefinition($format['key'], $locale, $options);
        }

        return $definitionsArray;
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
     * @param string $filename
     * @param string $newExtension
     *
     * @return string
     */
    protected function replaceExtension($filename, $mimeType)
    {
        $info = pathinfo($filename);

        switch ($mimeType) {
            case 'image/png':
            case 'image/svg+xml':
                $extension = 'png';
                break;
            case 'image/gif':
                $extension = 'gif';
                break;
            default:
                $extension = 'jpg';
        }

        return $info['filename'] . '.' . $extension;
    }

    /**
     * @param MediaInterface $media
     *
     * @return FileVersion
     *
     * @throws ImageProxyMediaNotFoundException
     */
    private function getLatestFileVersion(MediaInterface $media)
    {
        foreach ($media->getFiles() as $file) {
            $version = $file->getVersion();
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    return $fileVersion;
                }
            }
            break;
        }

        throw new ImageProxyMediaNotFoundException('Media file version was not found');
    }

    /**
     * Returns true if the given mime type is supported, otherwise false.
     *
     * @param $mimeType
     *
     * @return bool
     */
    private function checkMimeTypeSupported($mimeType)
    {
        foreach ($this->supportedMimeTypes as $supportedMimeType) {
            if (fnmatch($supportedMimeType, $mimeType)) {
                return true;
            }
        }

        return false;
    }
}
