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

class MetadataProviderRegistry
{
    private $metadataProviders = [];

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
        if (null !== $this->container) {
            if (!$this->container->has($type)) {
                throw new MetadataProviderNotFoundException($type);
            }

            /** @var MetadataProviderInterface $metadataProvider */
            $metadataProvider = $this->container->get($type);

            return $metadataProvider;
        }

        if (!\array_key_exists($type, $this->metadataProviders)) {
            throw new MetadataProviderNotFoundException($type);
        }

        return $this->metadataProviders[$type];
    }

    /**
     * @deprecated since Sulu 2.6 use the constructor instead
     */
    public function addMetadataProvider(string $type, MetadataProviderInterface $metadataProvider)
    {
        $this->metadataProviders[$type] = $metadataProvider;
    }
}
