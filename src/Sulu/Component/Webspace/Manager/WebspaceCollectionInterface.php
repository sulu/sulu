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

/**
 * @extends \IteratorAggregate<Webspace>
 */
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
     * @internal
     * Setting a copy of the templated url with the values for the current request filled in.
     * These changes are being reset every time the kernel gets reset.
     *
     * @param array<string, array<string, PortalInformation>> $portalInformations
     */
    public function setPortalInformations(array $portalInformations): void;

    /**
     * Returns the portal informations for the given environment.
     *
     * @param array<int>|null $types Defines which type of portals are requested (null for all)
     *                               One of the Constants RequestAnalyzerInterface::MATCH_TYPE_*
     *
     * @return array<string, PortalInformation>
     */
    public function getPortalInformations(string $environment, ?array $types = null): array;

    /**
     * Returns all portal informations with placeholders.
     *
     * @return array<string, array<string, PortalInformation>>
     */
    public function getPortalInformationsTemplates(): array;

    public function isPortalInformationsHostReplaced(): bool;

    /**
     * @return array{webspaces:array, portalInformations:array}
     */
    public function toArray(): array;
}
