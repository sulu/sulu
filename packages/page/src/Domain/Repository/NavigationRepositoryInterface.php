<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Repository;

interface NavigationRepositoryInterface
{
    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>[]
     */
    public function getNavigationTree(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        array $properties = []
    ): array;

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>[]
     */
    public function getNavigationFlat(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        array $properties = []
    ): array;
}
