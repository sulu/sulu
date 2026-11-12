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

namespace Sulu\Redirect\Domain\Repository;

use Sulu\Redirect\Domain\Exception\RedirectNotFoundException;
use Sulu\Redirect\Domain\Model\RedirectInterface;

interface RedirectRepositoryInterface
{
    public function createNew(?string $id = null): RedirectInterface;

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     source?: string,
     *     sourceHost?: string|null,
     *     enabled?: bool,
     * } $filters
     * @param array{} $selects
     *
     * @throws RedirectNotFoundException
     */
    public function getOneBy(array $filters, array $selects = []): RedirectInterface;

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     source?: string,
     *     sourceHost?: string|null,
     *     enabled?: bool,
     * } $filters
     * @param array{} $selects
     */
    public function findOneBy(array $filters, array $selects = []): ?RedirectInterface;

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     source?: string,
     *     sourceHost?: string|null,
     *     enabled?: bool,
     * } $filters
     * @param array{} $sortBy
     * @param array{} $selects
     *
     * @return iterable<RedirectInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): iterable;

    public function findEnabledBySource(string $source, ?string $host): ?RedirectInterface;

    public function add(RedirectInterface $redirect): void;

    public function remove(RedirectInterface $redirect): void;
}
