<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class ActivitesListMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var MetadataProviderInterface
     */
    private $listMetadataProvider;

    /**
     * ActivityListMetadataProvider constructor.
     */
    public function __construct(MetadataProviderInterface $listMetadataProvider)
    {
        $this->listMetadataProvider = $listMetadataProvider;
    }

    /**
     * @param array<integer|string, mixed> $metadataOptions
     */
    public function getMetadata(string $key, string $locale, array $metadataOptions): MetadataInterface
    {
        /** @var ListMetadata $metaData */
        $metaData = $this->listMetadataProvider->getMetadata($key, $locale, $metadataOptions);

        if (!$metaData instanceof ListMetadata || 'activities' !== $key) {
            return $metaData;
        }

        if ($metadataOptions['showResource'] ?? false) {
            $resourceField = $metaData->getFields()['resource'];
            $resourceField->setVisibility('yes');
        }

        return $metaData;
    }
}
