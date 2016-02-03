<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Provider;

use Sulu\Component\Rest\ListBuilder\Metadata\ClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\ProviderInterface;

class ChainProvider implements ProviderInterface
{
    /**
     * @var ProviderInterface[]
     */
    private $chain = [];

    public function __construct(array $chain)
    {
        $this->chain = $chain;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForClass($className)
    {
        $classMetadata = new ClassMetadata($className);

        foreach ($this->chain as $provider) {
            $classMetadata->merge($provider->getMetadataForClass($className));
        }

        return $classMetadata;
    }
}
