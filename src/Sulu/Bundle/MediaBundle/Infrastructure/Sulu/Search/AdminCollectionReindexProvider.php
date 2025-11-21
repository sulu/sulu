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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;

/**
 * @phpstan-type Collection array{
 *     collectionId: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     title: string,
 *     locale: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminCollectionReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<CollectionMeta>
     */
    protected EntityRepository $collectionMetaRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $repository = $entityManager->getRepository(CollectionMeta::class);

        $this->collectionMetaRepository = $repository;
    }

    public function total(): int
    {
        return $this->collectionMetaRepository->count([]);
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $collections = $this->loadCollections($reindexConfig->getIdentifiers());

        /** @var Collection $collection */
        foreach ($collections as $collection) {
            yield [
                'id' => CollectionInterface::RESOURCE_KEY . '__' . ((string) $collection['collectionId']) . '__' . $collection['locale'],
                'resourceKey' => CollectionInterface::RESOURCE_KEY,
                'resourceId' => (string) $collection['collectionId'],
                'changedAt' => $collection['changed']->format('c'),
                'createdAt' => $collection['created']->format('c'),
                'title' => $collection['title'],
                'locale' => $collection['locale'],
                'securityContext' => MediaAdmin::SECURITY_CONTEXT,
            ];
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Collection>
     */
    private function loadCollections(array $identifiers = []): iterable
    {
        $qb = $this->collectionMetaRepository->createQueryBuilder('collectionMeta')
            ->select('collection.id as collectionId')
            ->addSelect('collection.created')
            ->addSelect('collection.changed')
            ->addSelect('collectionMeta.locale as locale')
            ->addSelect('collectionMeta.title as title')
            ->innerJoin('collectionMeta.collection', 'collection');

        if (0 < \count($identifiers)) {
            $conditions = [];
            $parameters = [];

            foreach ($identifiers as $index => $identifier) {
                $resourceKey = \explode('__', $identifier)[0];

                if (CollectionInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(collection.id = :id{$index} AND collectionMeta.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $qb->where(\implode(' OR ', $conditions));
            $qb->setParameters($parameters);
        }

        /** @var iterable<Collection> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
