<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

/**
 * @deprecated since version 2.2 and will be removed in version 3.0. Use InvalidDefaultTypeException instead.
 */
class InvalidBlockDefaultTypeException extends \Exception
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $defaultType;

    /**
     * @var string[]
     */
    private $availableTypes;

    /**
     * @param string[] $availableTypes
     */
    public function __construct(string $name, string $defaultType, array $availableTypes)
    {
        @trigger_deprecation('sulu/sulu', '2.2', 'The InvalidBlockDefaultTypeException is deprecated and will be removed in version 3.0. Use InvalidDefaultTypeException instead.');

        parent::__construct(\sprintf(
            'Block "%s" has invalid default-type "%s". Available types are %s',
            $name,
            $defaultType,
            \implode(
                ', ',
                \array_map(function($availableType) {
                    return '"' . $availableType . '"';
                }, $availableTypes)
            )
        ));
        $this->name = $name;
        $this->defaultType = $defaultType;
        $this->availableTypes = $availableTypes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultType(): string
    {
        return $this->defaultType;
    }

    /**
     * @return string[]
     */
    public function getAvailableTypes(): array
    {
        return $this->availableTypes;
    }
}
