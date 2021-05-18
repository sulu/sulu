<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\ListMetadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

/**
 * @internal This class should not be extended or initialized by any application outside of sulu.
 *           You can inject custom loaders or visitors to adjust the behaviour of the service in your project.
 */
class ListMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var iterable<ListMetadataLoaderInterface>
     */
    private $listMetadataLoaders;

    /**
     * @var iterable<ListMetadataVisitorInterface>
     */
    private $listMetadataVisitors;

    /**
     * @param iterable<ListMetadataLoaderInterface> $listMetadataLoaders
     * @param iterable<ListMetadataVisitorInterface>|null $listMetadataVisitors
     */
    public function __construct(
        iterable $listMetadataLoaders,
        ?iterable $listMetadataVisitors = null
    ) {
        $this->listMetadataLoaders = $listMetadataLoaders;
        $this->listMetadataVisitors = $listMetadataVisitors ?: [];
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): MetadataInterface
    {
        $listMetadata = null;
        foreach ($this->listMetadataLoaders as $listMetadataLoader) {
            $listMetadata = $listMetadataLoader->getMetadata($key, $locale, $metadataOptions);
            if ($listMetadata) {
                break;
            }
        }

        if (!$listMetadata instanceof ListMetadata) {
            throw new MetadataNotFoundException('list', $key);
        }

        foreach ($this->listMetadataVisitors as $listMetadataVisitor) {
            $listMetadataVisitor->visitListMetadata($listMetadata, $key, $locale, $metadataOptions);
        }

        return $listMetadata;
    }
}
