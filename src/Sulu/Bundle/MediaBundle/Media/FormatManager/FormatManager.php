<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidImageFormat;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ParameterBag;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ParameterBag|null
     */
    private $supportedImageFormats;

    /**
     * @param string $saveImage
     * @param array $responseHeaders
     * @param array $formats
     * @param LoggerInterface $logger
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        FormatCacheInterface $formatCache,
        ImageConverterInterface $converter,
        $saveImage,
        $responseHeaders,
        $formats,
        LoggerInterface $logger = null
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->formatCache = $formatCache;
        $this->converter = $converter;
        $this->saveImage = 'true' == $saveImage ? true : false;
        $this->responseHeaders = $responseHeaders;
        $this->fileSystem = new Filesystem();
        $this->formats = $formats;
        $this->logger = $logger ?: new NullLogger();
    }

    public function returnImage($id, $formatKey, $fileName)
    {
        $setExpireHeaders = false;

        try {
            $info = \pathinfo($fileName);

            if (!isset($info['extension'])) {
                throw new ImageProxyInvalidUrl('No `extension` was found in the url');
            }

            $imageFormat = $info['extension'];

            $media = $this->mediaRepository->findMediaByIdForRendering($id, $formatKey);

            if (!$media) {
                throw new ImageProxyMediaNotFoundException('Media was not found');
            }

            $fileVersion = $this->getLatestFileVersion($media);

            $supportedImageFormats = $this->converter->getSupportedOutputImageFormats($fileVersion->getMimeType());
            if (empty($supportedImageFormats)) {
                throw new InvalidMimeTypeForPreviewException($fileVersion->getMimeType() ?? '-null-');
            }

            if (!\in_array($imageFormat, $supportedImageFormats)) {
                throw new ImageProxyInvalidImageFormat(
                    \sprintf(
                        'Image format "%s" not supported. Supported image formats are: %s',
                        $imageFormat,
                        \implode(', ', $supportedImageFormats)
                    ));
            }

            // Convert Media to format.
            $responseContent = $this->converter->convert($fileVersion, $formatKey, $imageFormat);

            // HTTP Headers
            $status = 200;
            $setExpireHeaders = true;

            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($responseContent);

            // Save image.
            if ($this->saveImage) {
                $this->formatCache->save(
                    $responseContent,
                    $media->getId(),
                    $this->replaceExtension($fileVersion->getName(), $imageFormat),
                    $formatKey
                );
            }
        } catch (MediaException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $responseContent = null;
            $status = 404;
            $mimeType = null;
        }

        // Set header.
        $headers = $this->getResponseHeaders($mimeType, $setExpireHeaders);

        // Return image.
        return new Response($responseContent, $status, $headers);
    }

    public function getFormats($id, $fileName, $version, $subVersion, $mimeType)
    {
        $formats = [];

        $extensions = $this->converter->getSupportedOutputImageFormats($mimeType);

        if (empty($extensions)) {
            return [];
        }

        $originalExtension = $extensions[0];
        foreach ($this->formats as $format) {
            foreach ($extensions as $extension) {
                $formatUrl = $this->formatCache->getMediaUrl(
                    $id,
                    $this->replaceExtension($fileName, $extension),
                    $format['key'],
                    $version,
                    $subVersion
                );

                if ($extension === $originalExtension) {
                    $formats[$format['key']] = $formatUrl;
                }

                $formats[$format['key'] . '.' . $extension] = $formatUrl;
            }
        }

        return $formats;
    }

    public function purge($idMedia, $fileName, $mimeType)
    {
        $extensions = $this->converter->getSupportedOutputImageFormats($mimeType);

        if (empty($extensions)) {
            return true;
        }

        foreach ($this->formats as $format) {
            foreach ($extensions as $extension) {
                $this->formatCache->purge($idMedia, $this->replaceExtension($fileName, $extension), $format['key']);
            }
        }

        return true;
    }

    public function clearCache()
    {
        $this->formatCache->clear();
    }

    public function getFormatDefinition($formatKey, $locale = null)
    {
        if (!isset($this->formats[$formatKey])) {
            throw new FormatNotFoundException($formatKey);
        }

        $format = $this->formats[$formatKey];
        $title = $format['key'];

        if (\array_key_exists($locale, $format['meta']['title'])) {
            $title = $format['meta']['title'][$locale];
        } elseif (\count($format['meta']['title']) > 0) {
            $title = \array_values($format['meta']['title'])[0];
        }

        $formatArray = [
            'internal' => $format['internal'],
            'key' => $format['key'],
            'title' => $title,
            'scale' => $format['scale'],
        ];

        return $formatArray;
    }

    public function getFormatDefinitions($locale = null)
    {
        $definitionsArray = [];

        foreach ($this->formats as $format) {
            $definitionsArray[$format['key']] = $this->getFormatDefinition($format['key'], $locale);
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

        $headers = \array_merge(
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
     * Replace extension.
     *
     * @param string $filename
     * @param string $newExtension
     *
     * @return string
     */
    private function replaceExtension($filename, $newExtension)
    {
        $info = \pathinfo($filename);

        return $info['filename'] . '.' . $newExtension;
    }

    /**
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
}
