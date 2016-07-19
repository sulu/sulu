<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

/**
 * Provides configuration for a component.
 */
class ComponentConfiguration implements ComponentConfigurationInterface
{
    /**
     * @var string
     */
    private $component;

    /**
     * @var array
     */
    private $componentOptions;

    public function __construct($component, array $componentOptions)
    {
        $this->component = $component;
        $this->componentOptions = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->component;
    }

    /**
     * @param string $component
     */
    public function setComponent($component)
    {
        $this->component = $component;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->componentOptions;
    }

    /**
     * @param array $componentOptions
     */
    public function setComponentOptions($componentOptions)
    {
        $this->componentOptions = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'options' => $this->getOptions(),
        ];
    }
}
