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

use Sulu\Page\Domain\Exception\PageNotFoundException;
use Sulu\Page\Domain\Model\PageInterface;

/**
 * Implementation can be found in the following class:.
 *
 * @see Sulu\Page\Infrastructure\Doctrine\Repository\PageRepository
 */
interface PageRepositoryInterface
{
    /**
     * Groups are used in controllers and represents serialization / resolver group,
     * this allows that no controller need to be overwritten when something additional should be
     * loaded at that endpoint.
     */
    public const GROUP_SELECT_PAGE_ADMIN = 'page_admin';
    public const GROUP_SELECT_PAGE_WEBSITE = 'page_website';

    /**
     * Withs represents additional selects which can be load to join and select specific sub entities.
     * They are used by groups.
     */
    public const SELECT_PAGE_CONTENT = 'with-page-content';

    public function createNew(?string $uuid = null): PageInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     load_ghost_content?: bool,
     * } $filters
     * @param array{
     *     page_admin?: bool,
     *     page_website?: bool,
     *     with-page-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @throws PageNotFoundException
     */
    public function getOneBy(array $filters, array $selects = []): PageInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     * } $filters
     * @param array{
     *     page_admin?: bool,
     *     page_website?: bool,
     *     with-page-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     */
    public function findOneBy(array $filters, array $selects = []): ?PageInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     page?: int,
     *     limit?: int,
     *     navigationContexts?: string[],
     *     depth?: int,
     * } $filters
     * @param array{
     *     id?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     * } $sortBy
     * @param array{
     *     page_admin?: bool,
     *     page_website?: bool,
     *     with-page-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @return iterable<PageInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     page?: int,
     *     limit?: int,
     *     navigationContexts?: string[],
     *     depth?: int,
     * } $filters
     * @param array{
     *     id?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     * } $sortBy
     * @param array{
     *     page_admin?: bool,
     *     page_website?: bool,
     *     with-page-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @return iterable<PageInterface>
     */
    public function findByAsTree(array $filters = [], array $sortBy = [], array $selects = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     id?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     * } $sortBy
     *
     * @return iterable<string>
     */
    public function findIdentifiersBy(array $filters = [], array $sortBy = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     * } $filters
     */
    public function countBy(array $filters = []): int;

    public function add(PageInterface $page): void;

    public function remove(PageInterface $page): void;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     * } $filters
     */
    public function reorderOneBy(array $filters, int $position): void;

    /**
     * @param array{
     *     uuid?: string,
     * } $sourceFilters
     * @param array{
     *     uuid?: string,
     * } $targetParentFilters
     */
    public function moveOneBy(array $sourceFilters, array $targetParentFilters): void;
}
