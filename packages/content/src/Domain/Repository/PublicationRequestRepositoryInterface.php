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

namespace Sulu\Content\Domain\Repository;

use Sulu\Content\Domain\Exception\PublicationRequestNotFoundException;
use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequest;

interface PublicationRequestRepositoryInterface
{
    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     *
     * @throws PublicationRequestNotFoundException
     */
    public function getOneBy(array $filters): PublicationRequest;

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     */
    public function findOneBy(array $filters): ?PublicationRequest;

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     requestedAt?: 'asc'|'desc',
     * } $sortBy
     *
     * @return iterable<PublicationRequest>
     */
    public function findBy(array $filters = [], array $sortBy = []): iterable;

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     */
    public function countBy(array $filters = []): int;

    public function add(PublicationRequest $publicationRequest): void;

    public function remove(PublicationRequest $publicationRequest): void;
}
