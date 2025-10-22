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

use Sulu\CustomUrl\Domain\Exception\CustomUrlNotFoundException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;

interface CustomUrlRepositoryInterface
{
    public function createNew(?string $uuid = null): CustomUrlInterface;

    /**
     * @param array{
     *     targetDocument?: string,
     *     webspace?: string,
     * } $filters
     * @param array{} $sortBy
     * @param array{} $selects
     *
     * @return iterable<CustomUrlInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     url?: string,
     *     published?: bool,
     *     locale?: string,
     *     webspace?: string,
     * } $filters
     * @param array{} $selects
     *
     * @throws CustomUrlNotFoundException
     */
    public function getOneBy(array $filters, array $selects = []): CustomUrlInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     url?: string,
     *     published?: bool,
     *     locale?: string,
     *     webspace?: string,
     * } $filters
     * @param array{} $selects
     */
    public function findOneBy(array $filters, array $selects = []): ?CustomUrlInterface;

    public function add(CustomUrlInterface $customUrl): void;

    public function remove(CustomUrlInterface $customUrl): void;
}
