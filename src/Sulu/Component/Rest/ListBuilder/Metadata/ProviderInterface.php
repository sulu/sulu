<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Metadata\ClassMetadata;

interface ProviderInterface
{
    /**
     * Load metadata for the given class.
     *
     * @param string $className
     *
     * @return ClassMetadata
     */
    public function getMetadataForClass($className);
}
