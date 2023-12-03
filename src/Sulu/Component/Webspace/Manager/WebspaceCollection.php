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

/**
 * A collection of all webspaces and portals in a specific sulu installation.
 *
 * @implements \IteratorAggregate<Webspace>
 */
class WebspaceCollection implements WebspaceCollectionInterface
{
    /**
     * The portals of this specific sulu installation, prefiltered by the environment and url.
     *
     * @var array<string, PortalInformation>
     */
    private $portalInformations = [];

    /**
     * @param array<string, Webspace> $webspaces     All webspaces in a specific sulu installation (indexed by key)
     * @param array<string, Portal> $portals         All portals in a specific sulu installation (indexed by key)
     */
    public function __construct(
        private array $webspaces = [],
        private array $portals = [],
    ) { }

    public function getWebspace(string $key): ?Webspace
    {
        return $this->webspaces[$key] ?? null;
    }

    /**
     * @return array<string, Webspace>
     */
    public function getWebspaces(): array
    {
        return $this->webspaces;
    }

    public function getPortal(string $key): ?Portal
    {
        return $this->portals[$key] ?? null;
    }

    /**
     * Returns all the portals of this collection.
     *
     * @return array<string, Portal>
     */
    public function getPortals(): array
    {
        return $this->portals;
    }

    public function getPortalInformations(string $environment, array $types = null): array
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

    public function count(): int
    {
        return \count($this->webspaces);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->webspaces);
    }

    /**
     * Returns the content of these portals as array.
     *
     * @return array
     */
    public function toArray(): array
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
}
