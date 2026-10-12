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

namespace Sulu\Component\Webspace\Repository;

use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;

interface WebspaceRepositoryInterface
{
    /**
     * @internal This is only here for BC reasons
     *
     * @param array{
     *    cache_class: string,
     *    cache_dir: ?string,
     *    base_class: string,
     *    debug: bool,
     * } $options
     */
    public function setOptions(array $options): void;

    public function findWebspaceByKey(string $key): ?Webspace;

    public function findPortalByKey(string $key): ?Portal;

    /**
     * @return array<string, Webspace>
     */
    public function findAllWebspaces(): array;

    /**
     * @return Portal[]
     */
    public function findAllPortals(): array;

    /**
     * @param array<int>|null $types Defines which type of portals are requested (null for all)
     *
     * @return PortalInformation[]
     */
    public function findAllPortalInformations(?array $types = null): array;
}
