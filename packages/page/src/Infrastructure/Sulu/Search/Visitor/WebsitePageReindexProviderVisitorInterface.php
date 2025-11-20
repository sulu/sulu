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

namespace Sulu\Page\Infrastructure\Sulu\Search\Visitor;

/**
 * Interface for visitors that can process and manipulate reindex data
 * for website page dimension content during search indexing.
 */
interface WebsitePageReindexProviderVisitorInterface
{
    /**
     * Visit and manipulate the reindex data for a page dimension content.
     *
     * @param array<string, mixed> $result The raw query result containing page data
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function visit(array $result, array $data): array;
}
