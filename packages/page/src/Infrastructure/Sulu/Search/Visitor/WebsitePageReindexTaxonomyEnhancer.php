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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;

/**
 * @internal if you need to override this service, create a new service based on the WebsitePageReindexProviderEnhancerInterface
 * instead of extending this class
 *
 * @final
 */
class WebsitePageReindexTaxonomyEnhancer implements WebsitePageReindexProviderEnhancerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function enhanceQuery(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addSelect('dimensionContent.id AS dimensionContentId');
    }

    public function enhanceDocument(array $queryResult, array $document): array
    {
        $dimensionContentId = $queryResult['dimensionContentId'] ?? null;
        if (!\is_int($dimensionContentId)) {
            return $document;
        }

        $taxonomy = $this->loadTaxonomy($dimensionContentId);
        $categoryIds = $taxonomy['categoryIds'];
        $tagNames = $taxonomy['tagNames'];

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
     * @return array{categoryIds: int[], tagNames: string[]}
     */
    private function loadTaxonomy(int $dimensionContentId): array
    {
        /** @var array{categoryIds: string|null, tagNames: string|null} $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('GROUP_CONCAT(DISTINCT category.id) AS categoryIds')
            ->addSelect("GROUP_CONCAT(DISTINCT tag.name SEPARATOR '||') AS tagNames")
            ->from(PageDimensionContentInterface::class, 'dimensionContent')
            ->leftJoin('dimensionContent.excerptCategories', 'category')
            ->leftJoin('dimensionContent.excerptTags', 'tag')
            ->where('dimensionContent.id = :id')
            ->setParameter('id', $dimensionContentId)
            ->getQuery()
            ->getSingleResult();

        return [
            'categoryIds' => $this->parseCategoryIds($result['categoryIds']),
            'tagNames' => $this->parseTagNames($result['tagNames']),
        ];
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

        return \explode('||', $tagNames);
    }
}
