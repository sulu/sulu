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

namespace Sulu\Snippet\Domain\Repository;

use Sulu\Snippet\Domain\Exception\SnippetAreaNotFoundException;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;

/**
 * Implementation can be found in the following class.
 *
 * @see Sulu\Snippet\Infrastructure\Doctrine\Repository\SnippetAreaRepository
 */
interface SnippetAreaRepositoryInterface
{
    public function createNew(string $areaKey, string $webspaceKey, ?string $uuid = null): SnippetAreaInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     webspaceKey?: string,
     *     areaKey?: string,
     * } $filters
     *
     * @throws SnippetAreaNotFoundException
     */
    public function getOneBy(array $filters): SnippetAreaInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     webspaceKey?: string,
     *     areaKey?: string,
     * } $filters
     */
    public function findOneBy(array $filters): ?SnippetAreaInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     webspaceKey?: string,
     *     areaKey?: string,
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     uuid?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     areaKey?: 'asc'|'desc',
     * } $sortBy
     *
     * @return \Generator<SnippetAreaInterface>
     */
    public function findBy(array $filters = [], array $sortBy = []): \Generator;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     webspaceKey?: string,
     *     areaKey?: string,
     * } $filters
     */
    public function countBy(array $filters = []): int;

    public function add(SnippetAreaInterface $snippetArea): void;

    public function remove(SnippetAreaInterface $snippetArea): void;
}
