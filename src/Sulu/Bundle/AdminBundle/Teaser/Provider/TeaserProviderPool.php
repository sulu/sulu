<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Teaser\Provider;

/**
 * Manages available providers.
 */
class TeaserProviderPool implements TeaserProviderPoolInterface
{
    /**
     * @param TeaserProviderInterface[] $providers
     */
    public function __construct(private array $providers)
    {
    }

    public function getProvider($name)
    {
        if (!$this->hasProvider($name)) {
            throw new ProviderNotFoundException($name, \array_keys($this->providers));
        }

        return $this->providers[$name];
    }

    public function hasProvider($name)
    {
        return \array_key_exists($name, $this->providers);
    }

    public function getConfiguration()
    {
        $configuration = [];
        foreach ($this->providers as $name => $provider) {
            $configuration[$name] = $provider->getConfiguration();
        }

        return $configuration;
    }
}
