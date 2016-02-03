<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Provider;

use Metadata\MetadataFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\ProviderInterface;

class MetadataProvider implements ProviderInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForClass($className)
    {
        $metadata = $this->metadataFactory->getMetadataForClass($className);

        if (null === $metadata) {
            return;
        }

        return $metadata;
    }
}
