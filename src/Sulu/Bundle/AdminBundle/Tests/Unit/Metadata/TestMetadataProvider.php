<?php

declare(strict_types=1);

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class TestMetadataProvider implements MetadataProviderInterface
{
    private MetadataInterface $metadata;

    public function __construct(){
        $this->metadata = new TypedFormMetadata();
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
