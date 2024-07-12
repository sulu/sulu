<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Exception;

/**
 * Missing class mapping configuration exception.
 */
class MissingClassMappingConfigurationException extends \Exception
{
    /**
     * @param string $className
     * @param string[] $available
     */
    public function __construct(private $className, private array $available)
    {
        parent::__construct(
            \sprintf(
                'Missing class mapping configuration for "%s". Available classes: ["%s"]',
                $className,
                \implode('", "', $available)
            )
        );
    }

    /**
     * Returns requested class-name.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Returns available configurations.
     *
     * @return string[]
     */
    public function getAvailable()
    {
        return $this->available;
    }
}
