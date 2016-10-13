<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Markup\Link;

/**
 * Contains all providers.
 */
class LinkProviderPool implements LinkProviderPoolInterface
{
    /**
     * @var LinkProviderInterface[]
     */
    private $providers;

    /**
     * @param LinkProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider($name)
    {
        if (!$this->hasProvider($name)) {
            throw new ProviderNotFoundException($name, array_keys($this->providers));
        }

        return $this->providers[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasProvider($name)
    {
        return array_key_exists($name, $this->providers);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = [];
        foreach ($this->providers as $name => $provider) {
            $configuration[$name] = $provider->getConfiguration();
        }

        return $configuration;
    }
}
