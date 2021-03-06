<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

/**
 * Interface for content query.
 */
interface ContentQueryExecutorInterface
{
    /**
     * Executes a query and returns loaded content as array.
     *
     * @param string $webspaceKey
     * @param string[] $locales
     * @param bool $flat
     * @param int $depth
     * @param int $limit
     * @param int $offset
     * @param bool $moveUp
     * @param ?int $permission
     *
     * @return array
     */
    public function execute(
        $webspaceKey,
        $locales,
        ContentQueryBuilderInterface $contentQueryBuilder,
        $flat = true,
        $depth = -1,
        $limit = null,
        $offset = null,
        $moveUp = false,
        $permission = null
    );
}
