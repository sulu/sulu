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
     * @var array
     */
    private $additionalSlides;

    /**
     * @param string $title
     * @param string $component
     * @param array $componentOptions
     * @param array $additionalSlides
     */
    public function __construct($title, $component, array $componentOptions = [], array $additionalSlides = [])
    {
        $this->title = $title;
        $this->component = $component;
        $this->componentOptions = $componentOptions;
        $this->additionalSlides = $additionalSlides;
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
     * Returns additional-slides.
     *
     * @return array
     */
    public function getAdditionalSlides()
    {
        return $this->additionalSlides;
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
            'additionalSlides' => $this->additionalSlides,
        ];
    }
}
