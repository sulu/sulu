<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class TestMetadataProvider implements MetadataProviderInterface
{
    private MetadataInterface $metadata;

    public function __construct(?MetadataInterface $metadata = null)
    {
        $this->metadata = $metadata ?? new TypedFormMetadata();
    }

    public function setMetaData(MetadataInterface $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions): MetadataInterface
    {
        return $this->metadata;
    }
}
