<?php

declare(strict_types=1);

namespace Sulu\Component\Webspace\Manager;

use Sulu\Component\Webspace\Webspace;
use Sulu\Component\Webspace\Portal;

interface WebspaceCollectionInterface extends \IteratorAggregate, \Countable
{
    public function getWebspace(string $key): ?Webspace;

    /**
     * @return array<string, Webspace>
     */
    public function getWebspaces(): array;

    public function getPortal(string $key): ?Portal;

    /**
     * @return array<string, Portal>
     */
    public function getPortals(): array;

    /**
     * Returns the portal informations for the given environment.
     *
     * @param array<string>|null $types Defines which type of portals are requested (null for all)
     *
     * @return PortalInformation[]
     */
    public function getPortalInformations(string $environment, array $types = null): array;

}
