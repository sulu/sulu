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

final class LinkProviderPool implements LinkProviderPoolInterface
{
    /**
     * @param array<string, LinkProviderInterface> $providers
     */
    public function __construct(private readonly array $providers)
    {
    }

    public function getProvider(string $name): LinkProviderInterface
    {
        if (!$this->hasProvider($name)) {
            throw new ProviderNotFoundException($name, \array_keys($this->providers));
        }

        return $this->providers[$name];
    }

    public function hasProvider(string $name): bool
    {
        return \array_key_exists($name, $this->providers);
    }

    public function getConfiguration(): array
    {
        $configuration = [];
        foreach ($this->providers as $name => $provider) {
            $configuration[$name] = $provider->getConfigurationBuilder()->getLinkConfiguration();
        }

        return $configuration;
    }
}
