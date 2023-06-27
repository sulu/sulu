<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $name
     * @param string[] $available
     */
    public function __construct(private $name, private $available)
    {
        parent::__construct(
            \sprintf('Strategy "%s" not found. Available: ["%s"]', $this->name, \implode('", "', $this->available))
        );
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
