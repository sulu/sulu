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
use Symfony\Component\HttpFoundation\File\File;

class VideoPropertiesProvider implements PropertiesProviderInterface
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
        $properties = [];

        try {
            $properties['duration'] = $this->ffprobe->format($file->getPathname())->get('duration');

            try {
                $dimensions = $this->ffprobe->streams($file->getPathname())->videos()->first()->getDimensions();
                $properties['width'] = $dimensions->getWidth();
                $properties['height'] = $dimensions->getHeight();
            } catch (\InvalidArgumentException $e) {
                // Exception is thrown if the video stream could not be obtained
            } catch (\RuntimeException $e) {
                // Exception is thrown if the dimension could not be extracted
            }
        } catch (ExecutableNotFoundException $e) {
            // Exception is thrown if ffmpeg is not installed -> video properties are not set
        }

        return $properties;
    }

    public static function supports(File $file): bool
    {
        return \fnmatch('video/*', $file->getMimeType());
    }
}
