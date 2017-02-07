<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository\Mapping;

/**
 * Build content repository mapping.
 */
class MappingBuilder
{
    /**
     * Create mapping-builder.
     *
     * @return MappingBuilder
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @var Mapping
     */
    private $mapping;

    private function __construct()
    {
        $this->mapping = new Mapping();
    }

    /**
     * Enable hydrate ghost pages.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function setHydrateGhost($enable)
    {
        $this->mapping->setHydrateGhost($enable);

        return $this;
    }

    /**
     * Enable follow internal-link relations.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function setFollowInternalLink($enable)
    {
        $this->mapping->setFollowInternalLink($enable);

        return $this;
    }

    /**
     * Enable hydrate shadow pages.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function setHydrateShadow($enable)
    {
        $this->mapping->setHydrateShadow($enable);

        return $this;
    }

    /**
     * Enable resolve url.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function setResolveUrl($enable)
    {
        $this->mapping->setResolveUrl($enable);

        return $this;
    }

    /**
     * Enable only published.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function setOnlyPublished($enable)
    {
        $this->mapping->setOnlyPublished($enable);

        return $this;
    }

    /**
     * Enable resolve concrete locales.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function setResolveConcreteLocales($enable)
    {
        $this->mapping->setResolveConcreteLocales($enable);

        return $this;
    }

    /**
     * Add properties to load.
     *
     * @param string[] $properties
     *
     * @return $this
     */
    public function addProperties($properties)
    {
        $this->mapping->addProperties($properties);

        return $this;
    }

    /**
     * Returns build mapping.
     *
     * @return Mapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }
}
