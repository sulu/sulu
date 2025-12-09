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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\OnClearEventArgs;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Route\Domain\Model\Route;
use Symfony\Contracts\Service\ResetInterface;

class RouteCleanupListener implements ResetInterface
{
    /**
     * @var array<DimensionContentInterface<ContentRichEntityInterface>&RoutableInterface>
     */
    private array $removedDimensionContents = [];  // @phpstan-ignore-line missingType.generics

    /**
     * @var array<array{resourceKey: string, resourceId: string}>
     */
    private array $removedContentRichEntityIds = [];

    /**
     * @var array<class-string, string|null>
     */
    private array $resourceFieldNameCache = [];

    public function preRemove(LifecycleEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $object = $args->getObject();

        if ($object instanceof DimensionContentInterface && $object instanceof RoutableInterface) {
            if (DimensionContentInterface::CURRENT_VERSION !== $object->getVersion()) {
                return;
            }

            if (null === $object->getLocale()) {
                return;
            }

            $this->removedDimensionContents[] = $object;
        } elseif ($object instanceof ContentRichEntityInterface) {
            $dimensionContentClass = $object->createDimensionContent()::class;
            if (!\is_subclass_of($dimensionContentClass, RoutableInterface::class)) {
                return;
            }

            $this->removedContentRichEntityIds[] = [
                'resourceKey' => $dimensionContentClass::getResourceKey(),
                'resourceId' => (string) $object->getId(),
            ];
        }
    }

    public function onClear(OnClearEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $this->reset();
    }

    public function postFlush(PostFlushEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        if (0 === \count($this->removedDimensionContents) && 0 === \count($this->removedContentRichEntityIds)) {
            return;
        }

        $objectManager = $args->getObjectManager();
        $connection = $objectManager->getConnection();

        $classMetadata = $objectManager->getClassMetadata(Route::class);
        $routesTableName = $classMetadata->getTableName();

        $this->handleDimensionContentRemovals($objectManager, $connection, $routesTableName);
        $this->handleContentRichEntityRemovals($connection, $routesTableName);

        $this->reset();
    }

    public function reset(): void
    {
        $this->removedDimensionContents = [];
        $this->removedContentRichEntityIds = [];
        $this->resourceFieldNameCache = [];
    }

    private function handleDimensionContentRemovals(
        EntityManagerInterface $objectManager,
        Connection $connection,
        string $routesTableName
    ): void {
        if (0 === \count($this->removedDimensionContents)) {
            return;
        }

        $toDelete = [];
        foreach ($this->removedDimensionContents as $dimensionContent) {
            $resourceId = $dimensionContent->getResource()->getId();

            // Locale is guaranteed to be non-null due to filtering in preRemove
            $locale = $dimensionContent->getLocale();
            \assert(\is_string($locale));

            if (!$this->hasRemainingDimensions($objectManager, $dimensionContent, $resourceId, $locale)) {
                $resourceKey = $dimensionContent::getResourceKey();
                $toDelete[$resourceKey][$locale][] = (string) $resourceId;
            }
        }

        foreach ($toDelete as $resourceKey => $locales) {
            foreach ($locales as $locale => $resourceIds) {
                $this->deleteRoutesBatch($connection, $routesTableName, $resourceKey, $resourceIds, $locale);
            }
        }
    }

    private function handleContentRichEntityRemovals(
        Connection $connection,
        string $routesTableName
    ): void {
        if (0 === \count($this->removedContentRichEntityIds)) {
            return;
        }

        $groupedByResourceKey = [];
        foreach ($this->removedContentRichEntityIds as $entityInfo) {
            $groupedByResourceKey[$entityInfo['resourceKey']][] = $entityInfo['resourceId'];
        }

        foreach ($groupedByResourceKey as $resourceKey => $resourceIds) {
            $this->deleteRoutesBatch($connection, $routesTableName, $resourceKey, $resourceIds);
        }
    }

    /**
     * @param array<string> $resourceIds
     */
    private function deleteRoutesBatch(
        Connection $connection,
        string $routesTableName,
        string $resourceKey,
        array $resourceIds,
        ?string $locale = null
    ): void {
        if (0 === \count($resourceIds)) {
            return;
        }

        // Delete main routes
        $this->executeRouteDeletion($connection, $routesTableName, $resourceKey, $resourceIds, $locale);

        // Delete history routes
        $historyResourceIds = \array_map(fn (string $id): string => $resourceKey . '::' . $id, $resourceIds);
        $this->executeRouteDeletion($connection, $routesTableName, Route::HISTORY_RESOURCE_KEY, $historyResourceIds, $locale);
    }

    /**
     * @param array<string> $resourceIds
     */
    private function executeRouteDeletion(
        Connection $connection,
        string $table,
        string $resourceKey,
        array $resourceIds,
        ?string $locale
    ): void {
        $queryBuilder = $connection->createQueryBuilder()
            ->delete($table)
            ->where('resource_key = :resourceKey')
            ->andWhere('resource_id IN (:resourceIds)')
            ->setParameter('resourceKey', $resourceKey)
            ->setParameter('resourceIds', $resourceIds, ArrayParameterType::STRING);

        if (null !== $locale) {
            $queryBuilder
                ->andWhere('locale = :locale')
                ->setParameter('locale', $locale);
        }

        $queryBuilder->executeStatement();
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    private function hasRemainingDimensions(
        EntityManagerInterface $objectManager,
        DimensionContentInterface $dimensionContent,
        mixed $resourceId,
        string $locale
    ): bool {
        $resourceFieldName = $this->getResourceFieldName($objectManager, $dimensionContent::class);

        if (null === $resourceFieldName) {
            return false;
        }

        $queryBuilder = $objectManager->createQueryBuilder()
            ->select('COUNT(dimensionContent.id)')
            ->from($dimensionContent::class, 'dimensionContent')
            ->where('dimensionContent.' . $resourceFieldName . ' = :resourceId')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('resourceId', $resourceId)
            ->setParameter('locale', $locale)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param class-string $dimensionContentClass
     */
    private function getResourceFieldName(
        EntityManagerInterface $objectManager,
        string $dimensionContentClass
    ): ?string {
        if (\array_key_exists($dimensionContentClass, $this->resourceFieldNameCache)) {
            return $this->resourceFieldNameCache[$dimensionContentClass];
        }

        $metadata = $objectManager->getClassMetadata($dimensionContentClass);
        $resourceFieldName = null;

        foreach ($metadata->getAssociationMappings() as $fieldName => $mapping) {
            // can be changed to $mapping->targetEntity if min version is doctrine/orm 3+
            // @phpstan-ignore-next-line argument.type
            if (\is_a($mapping['targetEntity'], ContentRichEntityInterface::class, true)) {
                $resourceFieldName = $fieldName;
                break;
            }
        }

        $this->resourceFieldNameCache[$dimensionContentClass] = $resourceFieldName;

        return $resourceFieldName;
    }
}
