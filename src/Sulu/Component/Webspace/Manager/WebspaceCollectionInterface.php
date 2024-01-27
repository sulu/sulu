<?php

declare(strict_types=1);

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
     * @return array<string, PortalInformation>
     */
    public function getPortalInformations(string $environment, ?array $types = null): array;
}
