<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Palette\RGB;
use Imagine\Imagick\Imagine as ImagickImagine;
use RuntimeException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidFormatOptionsException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileTypeException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager\ManagerInterface;

/**
 * Sulu imagine converter for media
 */
class ImagineImageConverter implements ImageConverterInterface
{

    /**
     * @var GdImagine|ImagickImagine
     */
    protected $image;

    /**
     * @var ManagerInterface
     */
    protected $commandManager;

    /**
     * @param ManagerInterface $commandManager
     */
    public function __construct(ManagerInterface $commandManager)
    {
        $this->commandManager = $commandManager;
    }

    /**
     * @return GdImagine
     */
    protected function newImage()
    {
        try {
            return new ImagickImagine();
        } catch (RuntimeException $ex) {
            return new GdImagine();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convert($originalPath, $formatOptions)
    {
        $imagine = $this->newImage();

        try {
            $this->image = $imagine->open($originalPath);
        } catch (\RuntimeException $e) {
            if (file_exists($originalPath)) {
                throw new InvalidFileTypeException($e->getMessage());
            }
            throw new ImageProxyMediaNotFoundException($e->getMessage());
        }

        $this->toRGB();

        if (!isset($formatOptions['commands'])) {
            throw new ImageProxyInvalidFormatOptionsException('Commands not found.');
        }
        if (isset($formatOptions['commands'])) {
            foreach ($formatOptions['commands'] as $command) {
                if (!isset($command['parameters']) && !isset($command['action'])) {
                    throw new ImageProxyInvalidFormatOptionsException('Action or parameters not found.');
                }
                $this->call($command['action'], $command['parameters']);
            }
        }

        return $this->image;
    }

    /**
     * set the image palette to RGB
     */
    protected function toRGB()
    {
        if ($this->image->palette()->name() == 'cmyk') {
            $this->image->usePalette(new RGB());
        }
    }

    /**
     * @param $command
     * @param $parameters
     * @throws ImageProxyInvalidFormatOptionsException
     */
    public function call($command, $parameters)
    {
        if (count($this->image->layers())) {
            $counter = 0;
            foreach ($this->image->layers() as $layer) {
                $counter++;
                $this->commandManager->get($command)->execute($layer, $parameters);
                if ($counter == 1) {
                    /** @var \Imagine\Imagick\Image|\Imagine\Gd\Image $image */
                    $image = $layer; // use first layer as main image
                } else {
                    $image->layers()->add($layer);
                }
            }
            $this->image = $image;
        } else {
            $this->commandManager->get($command)->execute($this->image, $parameters);
        }
    }
}
