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

namespace Sulu\Content\Infrastructure\Doctrine\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * @internal
 */
final class CascadeDeleteWorkflowTransitionRequestListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();

        $removedDimensionContents = [];
        $filters = [];

        foreach ($entityManager->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof DimensionContentInterface) {
                $locale = $entity->getLocale();
                if (null === $locale || DimensionContentInterface::CURRENT_VERSION !== $entity->getVersion()) {
                    continue;
                }

                $scope = \sprintf('%s:%s:%s', $entity::getResourceKey(), (string) $entity->getResource()->getId(), $locale);
                $removedDimensionContents[$scope][] = $entity;

                continue;
            }

            if ($entity instanceof ContentRichEntityInterface) {
                $filters[] = [
                    'resourceKey' => $this->getDimensionContentClass($entityManager, $entity)::getResourceKey(),
                    'resourceId' => (string) $entity->getId(),
                ];
            }
        }

        foreach ($removedDimensionContents as $removed) {
            $dimensionContent = $removed[0];
            $locale = (string) $dimensionContent->getLocale();

            // An unpublish removes only the live dimension while the draft stays, and the locale keeps its
            // review history. Only the last dimension of a locale takes the requests with it.
            if ($this->countDimensionContents($entityManager, $dimensionContent, $locale) > \count($removed)) {
                continue;
            }

            $filters[] = [
                'resourceKey' => $dimensionContent::getResourceKey(),
                'resourceId' => (string) $dimensionContent->getResource()->getId(),
                'locale' => $locale,
            ];
        }

        if ([] === $filters) {
            return;
        }

        $repository = $entityManager->getRepository(WorkflowTransitionRequest::class);

        foreach ($filters as $filter) {
            foreach ($repository->findBy($filter) as $workflowTransitionRequest) {
                $entityManager->remove($workflowTransitionRequest);
            }
        }
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     *
     * @return class-string<T>
     */
    private function getDimensionContentClass(EntityManagerInterface $entityManager, ContentRichEntityInterface $contentRichEntity): string
    {
        /** @var class-string<T> */
        return $entityManager->getClassMetadata($contentRichEntity::class)
            ->getAssociationMapping('dimensionContents')['targetEntity'];
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    private function countDimensionContents(EntityManagerInterface $entityManager, DimensionContentInterface $dimensionContent, string $locale): int
    {
        $resource = $dimensionContent->getResource();
        $mapping = $entityManager->getClassMetadata($resource::class)->getAssociationMapping('dimensionContents');

        /** @var string $mappedBy */
        $mappedBy = $mapping['mappedBy'];

        /** @var class-string $targetEntity */
        $targetEntity = $mapping['targetEntity'];

        return (int) $entityManager->createQueryBuilder()
            ->select('COUNT(dimensionContent.id)')
            ->from($targetEntity, 'dimensionContent')
            ->where('dimensionContent.' . $mappedBy . ' = :resource')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('resource', $resource)
            ->setParameter('locale', $locale)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
