<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\PropertiesProvider;

use FFMpeg\Exception\ExecutableNotFoundException;
use FFMpeg\FFProbe;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;

class VideoPropertiesProvider implements MediaPropertiesProviderInterface
{
    /**
     * @var FFProbe
     */
    private $ffprobe;

    public function __construct(FFProbe $ffprobe)
    {
        $this->ffprobe = $ffprobe;
    }

    public function provide(File $file): array
    {
        $mimeType = $file->getMimeType();

        if (!$mimeType || !\fnmatch('video/*', $mimeType)) {
            return [];
        }

        $properties = [];

        try {
            $properties['duration'] = $this->ffprobe->format($file->getPathname())->get('duration');

            try {
                $video = $this->ffprobe->streams($file->getPathname())->videos()->first();

                if (null !== $video) {
                    $dimensions = $video->getDimensions();
                    $properties['width'] = $dimensions->getWidth();
                    $properties['height'] = $dimensions->getHeight();
                }
            } catch (InvalidArgumentException $e) {
                // @ignoreException Exception is thrown if the video stream could not be obtained
            } catch (RuntimeException $e) { // @phpstan-ignore-line
                // @ignoreException Exception is thrown if the dimension could not be extracted
            }
        } catch (ExecutableNotFoundException $e) {
            // @ignoreException Exception is thrown if ffmpeg is not installed -> video properties are not set
        }

        return $properties;
    }
}
