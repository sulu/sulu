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
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    public function getNavigationTree(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        ?string $segmentKey,
        int $depth = 1,
        array $properties = []
    ): array;

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    public function getNavigationFlat(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        ?string $segmentKey,
        int $depth = 1,
        array $properties = []
    ): array;

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    public function getNavigationFlatByUuid(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        ?string $navigationContext = null,
        array $properties = []
    ): array;

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    public function getNavigationTreeByUuid(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        ?string $navigationContext = null,
        array $properties = []
    ): array;

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    public function getBreadcrumb(
        string $uuid,
        string $locale,
        string $webspaceKey,
        array $properties = []
    ): array;
}
