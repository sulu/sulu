<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Teaser\Configuration;

/**
 * Contains configuration for teaser provider.
 */
class TeaserConfiguration implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $component;

    /**
     * @var array
     */
    protected $componentOptions;

    /**
     * @param string $title
     * @param string $component
     * @param array $componentOptions
     */
    public function __construct($title, $component, array $componentOptions = [])
    {
        $this->title = $title;
        $this->component = $component;
        $this->componentOptions = $componentOptions;
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns component-name.
     *
     * @return string
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Returns component-options.
     *
     * @return array
     */
    public function getComponentOptions()
    {
        return $this->componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'title' => $this->title,
            'component' => $this->component,
            'componentOptions' => $this->componentOptions,
        ];
    }
}
