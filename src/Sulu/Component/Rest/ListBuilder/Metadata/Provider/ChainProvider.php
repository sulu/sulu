<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Provider;

use Sulu\Component\Rest\ListBuilder\Metadata\ClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\ProviderInterface;

/**
 * Returns merged metadata for other providers.
 */
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
