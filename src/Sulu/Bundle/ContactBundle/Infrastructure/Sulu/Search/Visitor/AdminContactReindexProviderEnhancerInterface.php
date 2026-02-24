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

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\Visitor;

use Doctrine\ORM\QueryBuilder;

/**
 * Interface for enhancers that can process and manipulate reindex data
 * for admin contact during search indexing.
 */
interface AdminContactReindexProviderEnhancerInterface
{
    /**
     * Enhance the query builder before executing the reindex query.
     *
     * @param QueryBuilder $queryBuilder The query builder to enhance
     */
    public function enhanceQuery(QueryBuilder $queryBuilder): void;

    /**
     * Enhance and manipulate the reindex data for a contact.
     *
     * @param array<string, mixed> $queryResult The raw query result containing contact data
     * @param array<string, mixed> $document The document data to be indexed
     *
     * @return array<string, mixed>
     */
    public function enhanceDocument(array $queryResult, array $document): array;
}
