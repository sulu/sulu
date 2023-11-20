<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Resource\FileResource;

/**
 * A collection of all webspaces and portals in a specific sulu installation.
 *
 * @implements \IteratorAggregate<Webspace>
 */
class WebspaceCollection implements \IteratorAggregate, \Countable
{
    /**
     * All the portals in a specific sulu installation.
     *
     * @var array<string, Portal>
     */
    private $portals = [];

    /**
     * The portals of this specific sulu installation, prefiltered by the environment and url.
     *
     * @var array<string, PortalInformation>
     */
    private $portalInformations = [];

    /**
     * @param array<string, Webspace> $webspaces     All the webspaces in a specific sulu installation (indexed by key)
     */
    public function __construct(
        private array $webspaces = [],
    ) { }

    /**
     * Returns the portal with the given index.
     *
     * @param string $key The index of the portal
     *
     * @return Portal|null
     */
    public function getPortal($key)
    {
        return $this->portals[$key] ?? null;
    }

    /**
     * Returns the portal informations for the given environment.
     *
     * @param string $environment The environment to deliver
     * @param array|null $types Defines which type of portals are requested (null for all)
     *
     * @return PortalInformation[]
     */
    public function getPortalInformations($environment, $types = null)
    {
        if (!isset($this->portalInformations[$environment])) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown portal environment "%s"', $environment
            ));
        }
        if (null === $types) {
            return $this->portalInformations[$environment];
        }

        return \array_filter(
            $this->portalInformations[$environment],
            function(PortalInformation $portalInformation) use ($types) {
                return \in_array($portalInformation->getType(), $types);
            }
        );
    }

    /**
     * Returns the webspace with the given key.
     *
     * @param string $key The key of the webspace
     *
     * @return Webspace|null
     */
    public function getWebspace($key)
    {
        return $this->webspaces[$key] ?? null;
    }

    /**
     * Returns the length of the collection.
     *
     * @return int
     */
    public function count()
    {
        return \count($this->webspaces);
    }

    /**
     * Returns the length of the collection.
     *
     * @deprecated Use the count function instead.
     *
     * @return int
     */
    public function length()
    {
        return count($this);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->webspaces);
    }

    /**
     * Returns the content of these portals as array.
     *
     * @return array
     */
    public function toArray()
    {
        $collection = [];

        $webspaces = [];
        foreach ($this->webspaces as $webspace) {
            $webspaces[] = $webspace->toArray();
        }

        $portalInformations = [];
        foreach ($this->portalInformations as $environment => $environmentPortalInformations) {
            $portalInformations[$environment] = [];

            foreach ($environmentPortalInformations as $environmentPortalInformation) {
                $portalInformations[$environment][$environmentPortalInformation->getUrl()] = $environmentPortalInformation->toArray();
            }
        }

        $collection['webspaces'] = $webspaces;
        $collection['portalInformations'] = $portalInformations;

        return $collection;
    }

    /**
     * @return Webspace[]
     */
    public function getWebspaces()
    {
        return $this->webspaces;
    }

    /**
     * Returns all the portals of this collection.
     *
     * @return Portal[]
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * Sets the portals for this collection.
     *
     * @param Portal[] $portals
     */
    public function setPortals($portals)
    {
        $this->portals = $portals;
    }

    /**
     * Sets the portal Information for this collection.
     *
     * @param array $portalInformations
     */
    public function setPortalInformations($portalInformations)
    {
        $this->portalInformations = $portalInformations;
    }
}
