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

use Doctrine\ORM\QueryBuilder;

/**
 * @internal if you need to override this service, create a new service based on the WebsiteArticleReindexProviderEnhancerInterface
 * instead of extending this class
 *
 * @final
 */
class WebsiteArticleReindexTaxonomyEnhancer implements WebsiteArticleReindexProviderEnhancerInterface
{
    public function enhanceQuery(QueryBuilder $queryBuilder): void
    {
        $queryBuilder
            ->leftJoin('dimensionContent.excerptCategories', 'category')
            ->leftJoin('dimensionContent.excerptTags', 'tag')
            ->addSelect('GROUP_CONCAT(DISTINCT category.id) AS categoryIds')
            ->addSelect('GROUP_CONCAT(DISTINCT tag.name) AS tagNames')
            ->addGroupBy('dimensionContent.id');
    }

    public function enhanceDocument(array $queryResult, array $document): array
    {
        $rawCategoryIds = $queryResult['categoryIds'] ?? null;
        $rawTagNames = $queryResult['tagNames'] ?? null;

        $categoryIds = $this->parseCategoryIds(\is_string($rawCategoryIds) ? $rawCategoryIds : null);
        $tagNames = $this->parseTagNames(\is_string($rawTagNames) ? $rawTagNames : null);

        if ([] === $categoryIds && [] === $tagNames) {
            return $document;
        }

        $metadata = \is_array($document['metadata'] ?? null) ? $document['metadata'] : [];
        $excerpt = \is_array($metadata['excerpt'] ?? null) ? $metadata['excerpt'] : [];

        if ([] !== $categoryIds) {
            $excerpt['categoryIds'] = $categoryIds;
        }

        if ([] !== $tagNames) {
            $excerpt['tagNames'] = $tagNames;
        }

        $metadata['excerpt'] = $excerpt;
        $document['metadata'] = $metadata;

        return $document;
    }

    /**
     * @return int[]
     */
    private function parseCategoryIds(?string $categoryIds): array
    {
        if (null === $categoryIds || '' === $categoryIds) {
            return [];
        }

        return \array_map(static fn (string $id): int => (int) $id, \explode(',', $categoryIds));
    }

    /**
     * @return string[]
     */
    private function parseTagNames(?string $tagNames): array
    {
        if (null === $tagNames || '' === $tagNames) {
            return [];
        }

        return \explode(',', $tagNames);
    }
}
