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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;

/**
 * @internal if you need to override this service, create a new service based on the WebsitePageReindexProviderEnhancerInterface
 * instead of extending this class
 *
 * @final
 */
class WebsiteArticleReindexTaxonomyEnhancer implements WebsiteArticleReindexProviderEnhancerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function enhanceQuery(QueryBuilder $queryBuilder): void
    {
    }

    public function enhanceDocument(array $queryResult, array $document): array
    {
        $dimensionContentId = $queryResult['dimensionContentId'] ?? null;
        if (null === $dimensionContentId || !\is_int($dimensionContentId)) {
            return $document;
        }

        $categoryIds = $this->loadCategoryIds($dimensionContentId);
        $tagNames = $this->loadTagNames($dimensionContentId);

        if ([] !== $categoryIds || [] !== $tagNames) {
            $properties = \is_array($document['properties'] ?? null) ? $document['properties'] : [];
            $excerpt = \is_array($properties['excerpt'] ?? null) ? $properties['excerpt'] : [];

            if ([] !== $categoryIds) {
                $excerpt['categoryIds'] = $categoryIds;
            }

            if ([] !== $tagNames) {
                $excerpt['tagNames'] = $tagNames;
            }

            $properties['excerpt'] = $excerpt;
            $document['properties'] = $properties;
        }

        return $document;
    }

    /**
     * @return int[]
     */
    private function loadCategoryIds(int $dimensionContentId): array
    {
        /** @var list<array{id: int|null}> $results */
        $results = $this->entityManager->createQueryBuilder()
            ->select('category.id')
            ->from(ArticleDimensionContentInterface::class, 'dimensionContent')
            ->leftJoin('dimensionContent.excerptCategories', 'category')
            ->where('dimensionContent.id = :id')
            ->setParameter('id', $dimensionContentId)
            ->getQuery()
            ->getScalarResult();

        return \array_map(static fn (array $row): int => (int) $row['id'], \array_filter($results, static fn (array $row): bool => null !== $row['id']));
    }

    /**
     * @return string[]
     */
    private function loadTagNames(int $dimensionContentId): array
    {
        /** @var list<array{name: string|null}> $results */
        $results = $this->entityManager->createQueryBuilder()
            ->select('tag.name')
            ->from(ArticleDimensionContentInterface::class, 'dimensionContent')
            ->leftJoin('dimensionContent.excerptTags', 'tag')
            ->where('dimensionContent.id = :id')
            ->setParameter('id', $dimensionContentId)
            ->getQuery()
            ->getScalarResult();

        return \array_map(static fn (array $row): string => (string) $row['name'], \array_filter($results, static fn (array $row): bool => null !== $row['name']));
    }
}
