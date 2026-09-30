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

namespace Sulu\Content\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Repository\DimensionContentRepositoryInterface;

/**
 * @internal
 */
final class DimensionContentRepository implements DimensionContentRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContentMetadataInspectorInterface $contentMetadataInspector,
    ) {
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param array<string, mixed> $dimensionAttributes
     *
     * @return T|null
     */
    public function findOneBy(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): ?DimensionContentInterface
    {
        if (!$this->entityManager->contains($contentRichEntity)) {
            return null;
        }

        $mappedBy = $this->contentMetadataInspector->getDimensionContentPropertyName($contentRichEntity::class);
        $dimensionContentClass = $this->contentMetadataInspector->getDimensionContentClass($contentRichEntity::class);

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('dimensionContent')
            ->from($dimensionContentClass, 'dimensionContent')
            ->where('dimensionContent.' . $mappedBy . ' = :contentRichEntity')
            ->setParameter('contentRichEntity', $contentRichEntity)
            ->setMaxResults(1);

        foreach ($dimensionContentClass::getEffectiveDimensionAttributes($dimensionAttributes) as $key => $value) {
            if (null === $value) {
                $queryBuilder->andWhere('dimensionContent.' . $key . ' IS NULL');

                continue;
            }

            $queryBuilder->andWhere('dimensionContent.' . $key . ' = :' . $key)
                ->setParameter($key, $value);
        }

        /** @var T|null */
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
