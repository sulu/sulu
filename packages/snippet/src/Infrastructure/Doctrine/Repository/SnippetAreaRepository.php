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

namespace Sulu\Snippet\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Sulu\Snippet\Domain\Exception\SnippetAreaNotFoundException;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Webmozart\Assert\Assert;

class SnippetAreaRepository implements SnippetAreaRepositoryInterface
{
    /**
     * @var EntityRepository<SnippetAreaInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var class-string<SnippetAreaInterface>
     */
    private string $snippetAreaClassName;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->entityRepository = $this->entityManager->getRepository(SnippetAreaInterface::class);
        $this->snippetAreaClassName = $this->entityRepository->getClassName();
    }

    public function createNew(string $areaKey, string $webspaceKey, ?string $uuid = null): SnippetAreaInterface
    {
        $className = $this->snippetAreaClassName;

        return new $className($areaKey, $webspaceKey, $uuid);
    }

    public function getOneBy(array $filters): SnippetAreaInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        try {
            /** @var SnippetAreaInterface $snippetArea */
            $snippetArea = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new SnippetAreaNotFoundException($filters, 0, $e);
        }

        return $snippetArea;
    }

    public function findOneBy(array $filters): ?SnippetAreaInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        try {
            /** @var SnippetAreaInterface $snippetArea */
            $snippetArea = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }

        return $snippetArea;
    }

    public function countBy(array $filters = []): int
    {
        // The countBy method will ignore any page and limit parameters
        // for better developer experience we will strip them away here
        // instead of that the developer need to take that into account
        // in there call of the countBy method.
        unset($filters['page']); // @phpstan-ignore-line
        unset($filters['limit']); // @phpstan-ignore-line

        $queryBuilder = $this->createQueryBuilder($filters);

        $queryBuilder->select('COUNT(DISTINCT area.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return \Generator<SnippetAreaInterface>
     */
    public function findBy(array $filters = [], array $sortBy = []): \Generator
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy);

        /** @var iterable<SnippetAreaInterface> $snippetAreas */
        $snippetAreas = $queryBuilder->getQuery()->getResult();

        foreach ($snippetAreas as $snippetArea) {
            yield $snippetArea;
        }
    }

    public function add(SnippetAreaInterface $snippetArea): void
    {
        $this->entityManager->persist($snippetArea);
    }

    public function remove(SnippetAreaInterface $snippetArea): void
    {
        $this->entityManager->remove($snippetArea);
    }

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     webspaceKey?: string,
     *     areaKey?: string,
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     uuid?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     areaKey?: 'asc'|'desc',
     * } $sortBy
     */
    private function createQueryBuilder(array $filters, array $sortBy = []): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('area');

        $uuid = $filters['uuid'] ?? null;
        if (null !== $uuid) {
            Assert::string($uuid); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('area.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $uuids = $filters['uuids'] ?? null;
        if (null !== $uuids) {
            Assert::isArray($uuids); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('area.uuid IN(:uuids)')
                ->setParameter('uuids', $uuids);
        }

        $webspaceKey = $filters['webspaceKey'] ?? null;
        if (null !== $webspaceKey) {
            Assert::string($webspaceKey); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('area.webspaceKey = :webspaceKey')
                ->setParameter('webspaceKey', $webspaceKey);
        }

        $areaKey = $filters['areaKey'] ?? null;
        if (null !== $areaKey) {
            Assert::string($areaKey); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('area.areaKey = :areaKey')
                ->setParameter('areaKey', $areaKey);
        }

        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            Assert::integer($limit); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->setMaxResults($limit);
        }

        $page = $filters['page'] ?? null;
        if (null !== $page) {
            Assert::integer($page); // @phpstan-ignore staticMethod.alreadyNarrowedType
            Assert::notNull($limit);
            $offset = (int) ($limit * ($page - 1));
            $queryBuilder->setFirstResult($offset);
        }

        foreach ($sortBy as $field => $order) {
            switch ($field) {
                case 'uuid':
                    $queryBuilder->addOrderBy('area.uuid', $order);
                    break;
                case 'created':
                    $queryBuilder->addOrderBy('area.created', $order);
                    break;
                case 'areaKey':
                    $queryBuilder->addOrderBy('area.areaKey', $order);
                    break;
            }
        }

        return $queryBuilder;
    }
}
