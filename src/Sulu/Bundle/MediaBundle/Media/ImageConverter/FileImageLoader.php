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
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Exception\OriginalFileNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailServiceInterface;

/**
 * Loads the image from a media at the path located on the locale filesystem.
 */
class FileImageLoader implements ImageLoaderInterface
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
    public function load($path)
    {
        if (!file_exists($path)) {
            throw new ImageProxyMediaNotFoundException(sprintf('Original media at path "%s" not found', $path));
        }

        $mimeType = (new \finfo())->file($path, FILEINFO_MIME_TYPE);
        if ($mimeType === 'application/pdf') {
            return $this->convertPdfToImage($path);
        }

        if ($mimeType === 'image/vnd.adobe.photoshop') {
            return $this->convertPsdToImage($path);
        }

        if ($mimeType === 'image/svg+xml') {
            return $this->convertSvgToImage($path);
        }

        if (fnmatch('video/*', $mimeType)) {
            return $this->convertVideoToImage($path);
        }

        return file_get_contents($path);
    }

    /**
     * Converts the first page of pdf to an image using ghostscript.
     *
     * @param string $path
     *
     * @return string
     *
     * @throws GhostScriptNotFoundException
     */
    private function convertPdfToImage($path)
    {
        $command = $this->ghostScriptPath .
            ' -dNOPAUSE -sDEVICE=jpeg -dFirstPage=1 -dLastPage=1 -sOutputFile=%stdout -dJPEGQ=100 -r300x300 -q ' .
            $path .
            ' -c quit';

        $output = shell_exec($command);

        if (!$output) {
            throw new GhostScriptNotFoundException(
                'Ghostscript was not found at "' .
                $this->ghostScriptPath .
                '" or user has no Permission for "' .
                $path .
                '"'
            );
        }

        return $output;
    }

    /**
     * Converts a PSD to a png using imagine. Only works with Imagick and not with GD.
     *
     * @param $path
     *
     * @return string
     *
     * @throws InvalidMimeTypeForPreviewException
     */
    private function convertPsdToImage($path)
    {
        try {
            $image = $this->imagine->open($path);
            $image = $image->layers()[0];

            return $image->get('png');
        } catch (RuntimeException $e) {
            throw new InvalidMimeTypeForPreviewException('image/vnd.adobe.photoshop');
        }
    }

    private function convertSvgToImage($path)
    {
        $image = $this->imagine->open($path);

        return $image->get('png');
    }

    /**
     * Converts one frame of a video to an image using FFMPEG.
     *
     * @param $path
     *
     * @return string
     *
     * @throws OriginalFileNotFoundException
     */
    private function convertVideoToImage($path)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'media_original') . '.jpg';
        $this->videoThumbnail->generate($path, '00:00:02:01', $tempFile);

        $file = @file_get_contents($tempFile);

        unlink($tempFile);

        if (!$file) {
            throw new OriginalFileNotFoundException($path);
        }

        return $file;
    }
}
