<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Exception;

/**
 * This Exception will be raised when sitemap-provider was not found.
 */
class SitemapProviderNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $requested;

    /**
     * @var string[]
     */
    private $available;

    /**
     * @param string $requested
     * @param string[] $available
     */
    public function __construct($requested, array $available)
    {
        parent::__construct(
            sprintf('The requested provider "%s" was not found. Available: "%s"', $requested, implode('", "', $available))
        );

        $this->requested = $requested;
        $this->available = $available;
    }

    /**
     * Returns alias of requested provider.
     *
     * @return string
     */
    public function getRequested()
    {
        return $this->requested;
    }

    /**
     * Returns list of available alias.
     *
     * @return string[]
     */
    public function getAvailable()
    {
        return $this->available;
    }
}
