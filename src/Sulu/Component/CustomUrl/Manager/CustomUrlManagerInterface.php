<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\CustomUrl\Repository\RowsIterator;

interface CustomUrlManagerInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws TitleAlreadyExistsException
     */
    public function create(string $webspaceKey, array $data): CustomUrl;

    public function findByWebspaceKey(string $webspaceKey): RowsIterator;

    /**
     * @param array<string, mixed> $data
     */
    public function save(string $id, array $data): CustomUrl;

    /**
     * @param array<string> $ids
     */
    public function deleteByIds(array $ids): void;
}
