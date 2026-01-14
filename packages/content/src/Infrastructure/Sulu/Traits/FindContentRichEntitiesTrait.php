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

namespace Sulu\Content\Infrastructure\Sulu\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @template D of ContentRichEntityInterface
 *
 * @internal
 *
 * @deprecated since 3.0.3, implement entity loading directly in your teaser provider
 */
trait FindContentRichEntitiesTrait
{
    /**
     * @param string[]|int[] $ids
     * @param array<string, mixed> $attributes
     *
     * @return D[]
     */
    protected function findEntitiesByIds(array $ids, array $attributes = []): array
    {
        $entityIdField = $this->getEntityIdField();
        $entityManager = $this->getEntityManager();
        $contentRichEntityClass = $this->getContentRichEntityClass();
        $classMetadata = $entityManager->getClassMetadata($contentRichEntityClass);

        /** @var D[] $entities */
        $entities = $entityManager->createQueryBuilder()
            ->select(ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY)
            ->from($contentRichEntityClass, ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY)
            ->leftJoin(
                ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY . '.dimensionContents',
                'dimensionContent',
                Join::WITH,
                'dimensionContent.stage = :stage AND (dimensionContent.locale = :locale OR dimensionContent.locale IS NULL) AND dimensionContent.version = :version'
            )
            ->addSelect('dimensionContent')
            ->where(ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY . '.' . $entityIdField . ' IN (:ids)')
            ->getQuery()
            ->setParameter('ids', $ids)
            ->setParameter('stage', $attributes['stage'] ?? DimensionContentInterface::STAGE_LIVE)
            ->setParameter('locale', $attributes['locale'])
            ->setParameter('version', $attributes['version'] ?? DimensionContentInterface::CURRENT_VERSION)
            ->getResult();

        $idPositions = \array_flip($ids);

        \usort(
            $entities,
            function(ContentRichEntityInterface $a, ContentRichEntityInterface $b) use ($idPositions, $classMetadata, $entityIdField) {
                $aId = $classMetadata->getIdentifierValues($a)[$entityIdField];
                $bId = $classMetadata->getIdentifierValues($b)[$entityIdField];

                return ($idPositions[$aId] ?? 0) - ($idPositions[$bId] ?? 0);
            }
        );

        return $entities;
    }

    abstract protected function getEntityIdField(): string;

    /**
     * @return class-string<D>
     */
    abstract protected function getContentRichEntityClass(): string;

    abstract protected function getEntityManager(): EntityManagerInterface;
}
