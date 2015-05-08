<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\CommandInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager\ManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ImageFormatCompilerPass
 * @package Sulu\Bundle\MediaBundle\DependencyInjection
 */
class ImageCommandCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $commandServices = $container->findTaggedServiceIds('sulu_media.image.command');

        $commands = array();
        foreach ($commandServices as $serviceName => $tags) {
            $service = $container->get($serviceName);
            if ($service instanceof CommandInterface && isset($tags[0]) && isset($tags[0]['alias'])) {
                $commands[$tags[0]['alias']] = $serviceName;
            }
        }

        $container->setParameter('sulu_media.image.command.services', $commands);
    }
}
