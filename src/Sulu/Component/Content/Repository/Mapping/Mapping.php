<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository\Mapping;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Mapping definition for content-repository.
 */
class Mapping implements MappingInterface
{
    private bool $hydrateShadow = true;

    private bool $followInternalLink = true;

    private bool $hydrateGhost = true;

    private bool $resolveUrl = false;

    private bool $onlyPublished = false;

    /**
     * @var bool
     */
    private $resolveConcreteLocales;

    private \Doctrine\Common\Collections\ArrayCollection|array $properties;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
    }

    public function shouldHydrateShadow()
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

    public function shouldHydrateGhost()
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

    public function resolveUrl()
    {
        return $this->resolveUrl;
    }

    /**
     * @param bool $resolveUrl
     */
    public function setResolveUrl($resolveUrl)
    {
        $this->resolveUrl = $resolveUrl;
    }

    public function onlyPublished()
    {
        return $this->onlyPublished;
    }

    /**
     * @param bool $onlyPublished
     */
    public function setOnlyPublished($onlyPublished)
    {
        $this->onlyPublished = $onlyPublished;
    }

    public function resolveConcreteLocales()
    {
        return $this->resolveConcreteLocales;
    }

    /**
     * @param bool $resolveConcreteLocales
     */
    public function setResolveConcreteLocales($resolveConcreteLocales)
    {
        $this->resolveConcreteLocales = $resolveConcreteLocales;
    }

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
            if ('id' === $property) {
                continue;
            }

            if (!$this->properties->contains($property)) {
                $this->properties[] = $property;
            }
        }
    }
}
