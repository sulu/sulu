<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Imagine\Exception\RuntimeException;
use Imagine\Image\ImagineInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\GhostScriptNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Exception\OriginalFileNotFoundException;
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

    /**
     * {@inheritdoc}
     */
    public function extract($content)
    {
        $finfo = new \finfo();
        $mimeType = $finfo->buffer($content, FILEINFO_MIME_TYPE);

        if ($mimeType === 'application/pdf') {
            return $this->convertPdfToImage($content);
        }

        if ($mimeType === 'image/vnd.adobe.photoshop') {
            return $this->convertPsdToImage($content);
        }

        if ($mimeType === 'image/svg+xml') {
            return $this->convertSvgToImage($content);
        }

        if (fnmatch('video/*', $mimeType)) {
            return $this->convertVideoToImage($content);
        }

        return $content;
    }

    /**
     * Converts the first page of pdf to an image using ghostscript.
     *
     * @param string $content
     *
     * @return string
     *
     * @throws GhostScriptNotFoundException
     */
    private function convertPdfToImage($content)
    {
        $temporaryFilePath = $this->createTemporaryFile($content);

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

        return $output;
    }

    /**
     * Converts a PSD to a png using imagine. Only works with Imagick and not with GD.
     *
     * @param string $content
     *
     * @return string
     *
     * @throws InvalidMimeTypeForPreviewException
     */
    private function convertPsdToImage($content)
    {
        $temporaryFilePath = $this->createTemporaryFile($content);

        try {
            $image = $this->imagine->open($temporaryFilePath);
            $image = $image->layers()[0];

            unlink($temporaryFilePath);

            return $image->get('png');
        } catch (RuntimeException $e) {
            unlink($temporaryFilePath);
            throw new InvalidMimeTypeForPreviewException('image/vnd.adobe.photoshop');
        }
    }

    private function convertSvgToImage($content)
    {
        $temporaryFilePath = $this->createTemporaryFile($content);

        $image = $this->imagine->open($temporaryFilePath);
        unlink($temporaryFilePath);

        return $image->get('png');
    }

    /**
     * Converts one frame of a video to an image using FFMPEG.
     *
     * @param string $content
     *
     * @return string
     *
     * @throws OriginalFileNotFoundException
     */
    private function convertVideoToImage($content)
    {
        $temporaryFilePath = $this->createTemporaryFile($content);
        $this->videoThumbnail->generate($temporaryFilePath, '00:00:02:01', $temporaryFilePath);

        $extractedImage = file_get_contents($temporaryFilePath);
        unlink($temporaryFilePath);

        return $extractedImage;
    }

    /**
     * Returns the path to a temporary file containing the given content.
     *
     * @param string $content
     *
     * @return string
     */
    private function createTemporaryFile($content)
    {
        $path = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($path, $content);

        return $path;
    }
}
