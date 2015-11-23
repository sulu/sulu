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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Mapping definition for content-repository.
 */
class Mapping implements MappingInterface
{
    /**
     * @var bool
     */
    private $hydrateShadow = true;

    /**
     * @var bool
     */
    private $followInternalLink = true;

    /**
     * @var bool
     */
    private $hydrateGhost = true;

    /**
     * @var Collection
     */
    private $properties;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateShadow()
    {
        return $this->hydrateShadow;
    }

    /**
     * @param bool $hydrateShadow
     */
    public function setHydrateShadow($hydrateShadow)
    {
        $this->hydrateShadow = $hydrateShadow;
    }

    /**
     * {@inheritdoc}
     */
    public function followInternalLink()
    {
        return $this->followInternalLink;
    }

    /**
     * @param bool $followInternalLink
     */
    public function setFollowInternalLink($followInternalLink)
    {
        $this->followInternalLink = $followInternalLink;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateGhost()
    {
        return $this->hydrateGhost;
    }

    /**
     * @param bool $hydrateGhost
     */
    public function setHydrateGhost($hydrateGhost)
    {
        $this->hydrateGhost = $hydrateGhost;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->properties->toArray();
    }

    /**
     * @param string[] $properties
     */
    public function addProperties($properties)
    {
        foreach ($properties as $property) {
            if (!$this->properties->contains($property)) {
                $this->properties[] = $property;
            }
        }
    }
}
