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

namespace Sulu\CustomUrl\Domain\Repository;

use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;

interface CustomUrlRouteRepositoryInterface
{
    /**
     * @param array{
     *     path?: string,
     *     history?: bool,
     *     customUrl?: string,
     * } $filters
     * @param array{} $sortBy
     *
     * @return iterable<CustomUrlRouteInterface>
     */
    public function findBy(array $filters = [], array $sortBy = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     path?: string,
     *     history?: bool,
     *     customUrl?: string,
     * } $filters
     */
    public function findOneBy(array $filters): ?CustomUrlRouteInterface;

    public function remove(CustomUrlRouteInterface $route): void;
}
