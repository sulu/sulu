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

use Doctrine\ORM\QueryBuilder;

/**
 * Interface for enhancers that can process and manipulate reindex data
 * for website page dimension content during search indexing.
 */
interface WebsitePageReindexProviderEnhancerInterface
{
    /**
     * Enhance the query builder before executing the reindex query.
     *
     * @param QueryBuilder $queryBuilder The query builder to enhance
     */
    public function enhanceQuery(QueryBuilder $queryBuilder): void;

    /**
     * Enhance and manipulate the reindex data for a page dimension content.
     *
     * @param array<string, mixed> $queryResult The raw query result containing page data
     * @param array<string, mixed> $document The document data to be indexed
     *
     * @return array<string, mixed>
     */
    public function enhanceDocument(array $queryResult, array $document): array;
}
