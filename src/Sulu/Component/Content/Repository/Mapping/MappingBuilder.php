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
     * Enable follow ghost relations.
     *
     * @param bool $disable
     *
     * @return $this
     */
    public function disableHydrateGhost($disable = true)
    {
        $this->mapping->setHydrateGhost($disable);

        return $this;
    }

    /**
     * Enable follow internal-link relations.
     *
     * @param bool $disable
     *
     * @return $this
     */
    public function disableFollowInternalLink($disable = true)
    {
        $this->mapping->setFollowInternalLink($disable);

        return $this;
    }

    /**
     * Enable follow shadow relations.
     *
     * @param bool $disable
     *
     * @return $this
     */
    public function disableHydrateShadow($disable = true)
    {
        $this->mapping->setHydrateShadow($disable);

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
