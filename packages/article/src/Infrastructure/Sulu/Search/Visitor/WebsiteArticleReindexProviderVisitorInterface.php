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

namespace Sulu\Article\Infrastructure\Sulu\Search\Visitor;

/**
 * Interface for visitors that can process and manipulate reindex data
 * for website article dimension content during search indexing.
 */
interface WebsiteArticleReindexProviderVisitorInterface
{
    /**
     * Visit and manipulate the reindex data for an article dimension content.
     *
     * @param array<string, mixed> $result The raw query result containing article data
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function visit(array $result, array $data): array;
}
