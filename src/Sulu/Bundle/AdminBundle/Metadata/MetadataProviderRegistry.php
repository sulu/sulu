<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sulu\Bundle\AdminBundle\Exception\MetadataProviderNotFoundException;

final readonly class MetadataProviderRegistry
{
    public function __construct(
        private ?ContainerInterface $container = null,
    ) {
    }

    /**
     * @throws MetadataProviderNotFoundException
     * @throws ContainerExceptionInterface
     */
    public function getMetadataProvider(string $type): MetadataProviderInterface
    {
        if (!$this->container->has($type)) {
            throw new MetadataProviderNotFoundException($type);
        }

        /** @var MetadataProviderInterface $metadataProvider */
        $metadataProvider = $this->container->get($type);

        return $metadataProvider;
    }
}
