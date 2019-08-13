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
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class ListMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var ListMetadataLoaderInterface[]
     */
    private $listMetadataLoaders;

    public function __construct(iterable $listMetadataLoaders)
    {
        $this->listMetadataLoaders = $listMetadataLoaders;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): ?ListMetadata
    {
        $list = null;
        foreach ($this->listMetadataLoaders as $listMetadataLoader) {
            $list = $listMetadataLoader->getMetadata($key, $locale, $metadataOptions);
            if ($list) {
                break;
            }
        }

        if (!$list) {
            throw new MetadataNotFoundException('list', $key);
        }

        return $list;
    }
}
