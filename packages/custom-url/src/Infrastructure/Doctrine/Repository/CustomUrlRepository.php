<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sulu\CustomUrl\Domain\Exception\CustomUrlNotFoundException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;

class CustomUrlRepository implements CustomUrlRepositoryInterface
{
    /**
     * @var EntityRepository<CustomUrlInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var class-string<CustomUrlInterface>
     */
    private string $customUrlClassName;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->entityRepository = $entityManager->getRepository(CustomUrlInterface::class);
        $this->customUrlClassName = $this->entityRepository->getClassName();
    }

    public function createNew(?string $uuid = null): CustomUrlInterface
    {
        $className = $this->customUrlClassName;

        return new $className($uuid);
    }

    public function add(CustomUrlInterface $customUrl): void
    {
        $this->entityManager->persist($customUrl);
    }

    /**
     * @param array{
     *     targetDocument?: string,
     *     webspace?: string,
     * } $filters
     * @param array{} $sortBy
     * @param array{} $selects
     *
     * @return iterable<CustomUrlInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy, $selects);

        /** @var iterable<CustomUrlInterface> $result */
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * @param array{
     *     url?: string,
     *     published?: bool,
     *     locale?: string,
     * } $filters
     * @param array{} $selects
     */
    public function getOneBy(array $filters, array $selects = []): CustomUrlInterface
    {
        $result = $this->findOneBy($filters, $selects);

        if (!$result) {
            throw new CustomUrlNotFoundException($filters);
        }

        return $result;
    }

    public function findOneBy(array $filters, array $selects = []): ?CustomUrlInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        /** @var CustomUrlInterface|null $result */
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @param array{
     *     uuid?: string,
     *     url?: string,
     *     published?: bool,
     *     locale?: string,
     *     webspace?: string,
     *     targetDocument?: string,
     * } $filters
     * @param array{} $sortBy
     * @param array{} $selects
     */
    private function createQueryBuilder(array $filters, array $sortBy = [], array $selects = []): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('customUrl');

        if (isset($filters['uuid'])) {
            $queryBuilder->andWhere('customUrl.uuid = :uuid')
                ->setParameter('uuid', $filters['uuid']);
        }

        if (isset($filters['url'])) {
            $queryBuilder->join('customUrl.routes', 'route')
                ->andWhere('route.path = :url')
                ->setParameter('url', $filters['url']);
        }

        if (isset($filters['published'])) {
            $queryBuilder->andWhere('customUrl.published = :published')
                ->setParameter('published', $filters['published']);
        }

        if (isset($filters['locale'])) {
            $queryBuilder->andWhere('customUrl.targetLocale = :locale')
                ->setParameter('locale', $filters['locale']);
        }

        if (isset($filters['webspace'])) {
            $queryBuilder->andWhere('customUrl.webspace = :webspace')
                ->setParameter('webspace', $filters['webspace']);
        }

        if (isset($filters['targetDocument'])) {
            $queryBuilder->andWhere('customUrl.targetDocument = :targetDocument')
                ->setParameter('targetDocument', $filters['targetDocument']);
        }

        $queryBuilder->andWhere('customUrl.targetDocument IS NOT NULL');

        return $queryBuilder;
    }

    public function remove(CustomUrlInterface $customUrl): void
    {
        $this->entityManager->remove($customUrl);
    }
}
