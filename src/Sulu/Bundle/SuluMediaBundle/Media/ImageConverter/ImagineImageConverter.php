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

use Imagine\Gd\Imagine;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidFormatOptionsException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidImageFormat;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\CommandInterface;

class ImagineImageConverter implements ImageConverterInterface {

    /**
     * @var array
     */
    private $formats;

    /**
     * @var Imagine
     */
    protected $image;

    /**
     * @var TODO
     */
    protected $commandManager;

    /**
     * @param $formats
     * @param $commandManager
     */
    public function __construct($formats, $commandManager)
    {
        $this->formats = $formats;
        $this->commandManager = $commandManager;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($originalPath, $format)
    {
        $formatOptions = $this->getFormatOptions($format);

        $this->image = new Imagine();
        $this->image->open($originalPath);
        if (!isset($formatOptions['command']) && !isset($formatOptions['parameters'])) {
            throw new ImageProxyInvalidFormatOptionsException('Command or parameters not found.');
        }
        if (isset($formatOptions['commands'])) {
            foreach ($formatOptions['commands'] as $command) {
                $this->call($command['action'], $command['parameters']);
            }
        }
    }

    /**
     * return the options for the given format
     * @param $format
     * @return array
     * @throws ImageProxyInvalidImageFormat
     */
    protected function getFormatOptions($format)
    {
        $formatOptions = null;

        foreach ($this->formats as $options) {
            if ($options['name'] == $format) {
                $formatOptions = $options;
            }
        }

        if (!$formatOptions) {
            throw new ImageProxyInvalidImageFormat('Format was not found');
        }

        return $formatOptions;
    }

    /**
     * @param $command
     * @param $parameters
     * @throws ImageProxyInvalidFormatOptionsException
     */
    public function call($command, $parameters)
    {
        $this->commandManager->get($command)->execute($this->image, $parameters);
    }

} 
