<?php

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Metadata;


use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class ActivityListMetadataProvider implements MetadataProviderInterface
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

    public function getMetadata(string $key, string $locale, array $metadataOptions): MetadataInterface
    {
        $metadataOptions = $this->listMetadataProvider->getMetadata($key, $locale, $metadataOptions);

        if ($key !== 'activities') {
            return $metadataOptions;
        }

        /** @var FieldMetadata $resourceField */
        $resourceField = $metadataOptions->getFields()['resource'];
        $resourceField->setVisibility('yes');

        return $metadataOptions;
    }
}
