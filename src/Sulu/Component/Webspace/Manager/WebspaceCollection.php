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
 */
class WebspaceCollection implements WebspaceCollectionInterface
{
    /** @var array<string, array<string, PortalInformation>>|null */
    private ?array $portalInformations = null;

    /**
     * @param array<string, Webspace> $webspaces All webspaces (indexed by key)
     * @param array<string, Portal> $portals All portals (indexed by key)
     * @param array<string, array<string, PortalInformation>> $portalInformationsTemplate
     *                                                                                    All portalInformations (indexed by environment and key)
     *                                                                                    These portalInformations still contain the placeholders in the URL
     */
    public function __construct(
        private array $webspaces = [],
        private array $portals = [],
        private array $portalInformationsTemplate = [],
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

    public function getPortalInformationsTemplates(): array
    {
        return $this->portalInformationsTemplate;
    }

    public function setPortalInformations(array $portalInformations): void
    {
        $this->portalInformations = $portalInformations;
    }

    public function isPortalInformationsHostReplaced(): bool
    {
        return null !== $this->portalInformations;
    }

    public function getPortalInformations(string $environment, ?array $types = null): array
    {
        $portalInformations = $this->portalInformations ?? $this->portalInformationsTemplate;

        if (!isset($portalInformations[$environment])) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown portal environment "%s"', $environment
            ));
        }
        if (null === $types) {
            return $portalInformations[$environment];
        }

        return \array_filter(
            $portalInformations[$environment],
            function(PortalInformation $portalInformation) use ($types) {
                return \in_array($portalInformation->getType(), $types);
            }
        );
    }

    public function count(): int
    {
        return \count($this->webspaces);
    }

    /**
    * @return \ArrayIterator<string, Webspace>
    */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->webspaces);
    }

    public function reset(): void
    {
        $this->portalInformations = null;
    }

    public function toArray(): array
    {
        $collection = [];

        $webspaces = [];
        foreach ($this->webspaces as $webspace) {
            $webspaces[] = $webspace->toArray();
        }

        $portalInformations = [];
        foreach ($this->portalInformations ?? $this->portalInformationsTemplate as $environment => $environmentPortalInformations) {
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
