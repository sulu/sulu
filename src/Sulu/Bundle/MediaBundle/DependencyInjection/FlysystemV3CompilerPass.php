<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemV3Storage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class FlysystemV3CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sulu_media.storage')) {
            return;
        }

        $mediaStorage = $container->getDefinition('sulu_media.storage');

        if (FlysystemV3Storage::class !== $mediaStorage->getClass()) {
            return;
        }

        /** @var Reference $reference */
        $reference = $mediaStorage->getArgument(1);
        $adapterDefinition = $container->findDefinition((string) $reference);

        if (LocalFilesystemAdapter::class === $adapterDefinition->getClass()) {
            $mediaStorage->setArgument(3, $adapterDefinition->getArgument(0));
        }
    }
}
