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
 * Indicates not existing provider.
 */
class ProviderNotFoundException extends \Exception
{
    /**
     * @param string $name
     * @param string[] $available
     */
    public function __construct(private $name, private array $available)
    {
        parent::__construct(
            \sprintf(
                'Provider "%s" does not exists. Available providers are: ["%s"]',
                $name,
                \implode('", "', $available)
            )
        );
    }

    /**
     * Returns requested provider.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns available providers.
     *
     * @return string[]
     */
    public function getAvailable()
    {
        return $this->available;
    }
}
