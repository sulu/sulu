<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass loads all the image formats defined in the files listed in the sulu_media configuration.
 */
class ImageFormatCompilerPass extends AbstractImageFormatCompilerPass
{
    /**
     * {@inheritdoc}
     */
    protected function getFiles(ContainerBuilder $container)
    {
        return $container->getParameter('sulu_media.image_format_files');
    }
}
