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
use Webmozart\Assert\Assert;

/**
 * A collection of all webspaces and portals in a specific sulu installation.
 *
 * @implements \IteratorAggregate<Webspace>
 */
class WebspaceCollection implements WebspaceCollectionInterface
{
    /**
     * @param array<string, Webspace> $webspaces All webspaces (indexed by key)
     * @param array<string, Portal> $portals All portals (indexed by key)
     * @param array<string, array<string, PortalInformation>> $portalInformations All portalInformations (indexed by environment and key)
     */
    public function __construct(
        private array $webspaces = [],
        private array $portals = [],
        private array $portalInformations = [],
    ) {
    }

    public function getWebspace(string $key): ?Webspace
    {
        return $this->webspaces[$key] ?? null;
    }

    public function getWebspaces(): array
    {
        return $this->webspaces;
    }

    public function getPortal(string $key): ?Portal
    {
        return $this->portals[$key] ?? null;
    }

    public function getPortals(): array
    {
        return $this->portals;
    }

    public function getPortalInformations(string $environment, ?array $types = null): array
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
