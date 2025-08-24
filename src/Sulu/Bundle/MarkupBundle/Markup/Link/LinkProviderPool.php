<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

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
            /** @var LinkConfiguration|LinkConfigurationBuilder|null $providerConfiguration */
            $providerConfiguration = $provider->getConfiguration();

            if ($providerConfiguration instanceof LinkConfiguration) {
                @trigger_deprecation(
                    'sulu/sulu',
                    '2.6',
                    'The LinkProvider should return a LinkConfigurationBuilder and not a LinkConfiguration. The LinkConfiguration will not be supported in 3.0.'
                );
            }

            $configuration[$name] = $providerConfiguration instanceof LinkConfigurationBuilder ? $providerConfiguration->getLinkConfiguration() : $providerConfiguration;
        }

        return \array_filter($configuration);
    }
}
