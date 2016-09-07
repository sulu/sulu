<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var string
     */
    private $className;

    /**
     * @var string[]
     */
    private $available;

    /**
     * @param string $className
     * @param string[] $available
     */
    public function __construct($className, array $available)
    {
        parent::__construct(
            sprintf(
                'Missing class mapping configuration for "%s". Available classes: ["%s"]',
                $className,
                implode('", "', $available)
            )
        );
        $this->className = $className;
        $this->available = $available;
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
