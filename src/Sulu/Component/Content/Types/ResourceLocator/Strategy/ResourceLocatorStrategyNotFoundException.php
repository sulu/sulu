<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

/**
 * Will be raised if the requested strategy is not available.
 */
class ResourceLocatorStrategyNotFoundException extends \Exception
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
     * @param string[] $available
     */
    public function __construct($name, $available)
    {
        parent::__construct(sprintf('Strategy "%s" not found. Available: ["%s"]', $name, implode('", "', $available)));

        $this->name = $name;
        $this->available = $available;
    }

    /**
     * Returns requested name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns available strategies.
     *
     * @return string[]
     */
    public function getAvailable()
    {
        return $this->available;
    }
}
