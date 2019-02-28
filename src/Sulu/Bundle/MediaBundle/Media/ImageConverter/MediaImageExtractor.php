<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Imagine\Exception\RuntimeException;
use Imagine\Image\ImagineInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\GhostScriptNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailServiceInterface;

/**
 * Loads the image from a media at the path located on the locale filesystem.
 */
class MediaImageExtractor implements MediaImageExtractorInterface
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var string
     */
    private $ghostScriptPath;

    /**
     * @var VideoThumbnailServiceInterface
     */
    private $videoThumbnail;

    public function __construct(
        ImagineInterface $imagine,
        VideoThumbnailServiceInterface $videoThumbnail,
        $ghostScriptPath
    ) {
        $this->imagine = $imagine;
        $this->ghostScriptPath = $ghostScriptPath;
        $this->videoThumbnail = $videoThumbnail;
    }

    public function extract($resource)
    {
        $mimeType = mime_content_type($resource);

        if ('application/pdf' === $mimeType) {
            return $this->convertPdfToImage($resource);
        }

        if ('image/vnd.adobe.photoshop' === $mimeType) {
            return $this->convertPsdToImage($resource);
        }

        if (fnmatch('video/*', $mimeType)) {
            return $this->convertVideoToImage($resource);
        }

        return $resource;
    }

    /**
     * Converts the first page of pdf to an image using ghostscript.
     *
     * @param resource $resource
     *
     * @return resource
     *
     * @throws GhostScriptNotFoundException
     */
    private function convertPdfToImage($resource)
    {
        $temporaryFilePath = $this->createTemporaryFile($resource);

        $command = $this->ghostScriptPath .
            ' -dNOPAUSE -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -sOutputFile=' . $temporaryFilePath . ' ' .
            '-dJPEGQ=100 -r300x300 -q ' . $temporaryFilePath . ' -c quit 2> /dev/null';

        shell_exec($command);
        $output = file_get_contents($temporaryFilePath);
        unlink($temporaryFilePath);

        if (!$output) {
            throw new GhostScriptNotFoundException(
                'Ghostscript was not found at "' .
                $this->ghostScriptPath .
                '"'
            );
        }

        return $this->createTemporaryResource($output);
    }

    /**
     * Converts a PSD to a png using imagine. Only works with Imagick and not with GD.
     *
     * @param resource $resource
     *
     * @return resource
     *
     * @throws InvalidMimeTypeForPreviewException
     */
    private function convertPsdToImage($resource)
    {
        $temporaryFilePath = $this->createTemporaryFile($resource);

        try {
            $image = $this->imagine->open($temporaryFilePath);
            $image = $image->layers()[0];

            unlink($temporaryFilePath);

            return $this->createTemporaryResource($image->get('png'));
        } catch (RuntimeException $e) {
            unlink($temporaryFilePath);

            throw new InvalidMimeTypeForPreviewException('image/vnd.adobe.photoshop');
        }
    }

    /**
     * Converts one frame of a video to an image using FFMPEG.
     *
     * @param resource $resource
     *
     * @return resource
     */
    private function convertVideoToImage($resource)
    {
        $temporaryFilePath = $this->createTemporaryFile($resource);
        $this->videoThumbnail->generate($temporaryFilePath, '00:00:02:01', $temporaryFilePath);

        $extractedImage = file_get_contents($temporaryFilePath);
        unlink($temporaryFilePath);

        return $this->createTemporaryResource($extractedImage);
    }

    /**
     * Create temporary resource which will removed on fclose or end of process.
     *
     * @param string $content
     *
     * @return resource
     */
    private function createTemporaryResource(string $content)
    {
        $tempResource = fopen('php://memory', 'r+');
        fwrite($tempResource, $content);
        rewind($tempResource);

        return $tempResource;
    }

    /**
     * Returns the path to a temporary file containing the given content.
     *
     * @param resource $resource
     *
     * @return string
     */
    private function createTemporaryFile($resource)
    {
        $path = tempnam(sys_get_temp_dir(), 'media');
        $tempResource = fopen($path, 'w');

        stream_copy_to_stream($resource, $tempResource);

        return $path;
    }
}
