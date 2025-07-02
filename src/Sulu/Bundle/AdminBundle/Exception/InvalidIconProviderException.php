<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

class InvalidIconProviderException extends \Exception
{
    /**
     * @param string[] $supportedProviders
     */
    public function __construct(private string $provider, private array $supportedProviders)
    {
        parent::__construct(
            \sprintf('The icon provider "%s" is not supported. SupportedProviders are "%s"', $provider, \implode('", "', $supportedProviders))
        );
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @return string[]
     */
    public function getSupportedProviders(): array
    {
        return $this->supportedProviders;
    }
}
