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
 * Indicates not existing provider.
 */
class ProviderNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $available;

    /**
     * @param string $name
     * @param \string[] $available
     */
    public function __construct($name, array $available)
    {
        parent::__construct(
            sprintf(
                'Provider "%s" does not exists. Available providers are: ["%s"]',
                $name,
                implode('", "', $available)
            )
        );

        $this->name = $name;
        $this->available = $available;
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
