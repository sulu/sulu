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

namespace Sulu\Redirect\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sulu\Redirect\Domain\Exception\RedirectNotFoundException;
use Sulu\Redirect\Domain\Model\RedirectInterface;
use Sulu\Redirect\Domain\Repository\RedirectRepositoryInterface;

class RedirectRepository implements RedirectRepositoryInterface
{
    /**
     * @var EntityRepository<RedirectInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var class-string<RedirectInterface>
     */
    private string $redirectClassName;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->entityRepository = $entityManager->getRepository(RedirectInterface::class);
        $this->redirectClassName = $this->entityRepository->getClassName();
    }

    public function createNew(?string $id = null): RedirectInterface
    {
        $className = $this->redirectClassName;

        return new $className($id);
    }

    public function getOneBy(array $filters, array $selects = []): RedirectInterface
    {
        $redirect = $this->findOneBy($filters, $selects);

        if (!$redirect) {
            throw new RedirectNotFoundException($filters);
        }

        return $redirect;
    }

    public function findOneBy(array $filters, array $selects = []): ?RedirectInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters);
        $queryBuilder->orderBy('redirect.id')
            ->setMaxResults(1);

        /** @var RedirectInterface|null $redirect */
        $redirect = $queryBuilder->getQuery()->getOneOrNullResult();

        return $redirect;
    }

    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        /** @var iterable<RedirectInterface> $redirects */
        $redirects = $queryBuilder->getQuery()->getResult();

        return $redirects;
    }

    public function findEnabledBySource(string $source, ?string $host): ?RedirectInterface
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('redirect')
            ->andWhere('redirect.source = :source')
            ->andWhere('redirect.enabled = true')
            ->setParameter('source', \mb_strtolower($source))
            // NULL hosts sort last on every platform, PostgreSQL puts NULL first in DESC order
            ->addSelect('CASE WHEN redirect.sourceHost IS NULL THEN 1 ELSE 0 END AS HIDDEN hostRank')
            ->orderBy('hostRank')
            ->addOrderBy('redirect.id')
            ->setMaxResults(1);

        if (null === $host) {
            $queryBuilder->andWhere('redirect.sourceHost IS NULL');
        } else {
            $queryBuilder->andWhere('redirect.sourceHost = :host OR redirect.sourceHost IS NULL')
                ->setParameter('host', \mb_strtolower($host));
        }

        /** @var RedirectInterface|null $redirect */
        $redirect = $queryBuilder->getQuery()->getOneOrNullResult();

        return $redirect;
    }

    public function add(RedirectInterface $redirect): void
    {
        $this->entityManager->persist($redirect);
    }

    public function remove(RedirectInterface $redirect): void
    {
        $this->entityManager->remove($redirect);
    }

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     source?: string,
     *     sourceHost?: string|null,
     *     enabled?: bool,
     * } $filters
     */
    private function createQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('redirect');

        if (isset($filters['id'])) {
            $queryBuilder->andWhere('redirect.id = :id')
                ->setParameter('id', $filters['id']);
        }

        if (isset($filters['ids'])) {
            $queryBuilder->andWhere('redirect.id IN (:ids)')
                ->setParameter('ids', $filters['ids']);
        }

        if (isset($filters['source'])) {
            $queryBuilder->andWhere('redirect.source = :source')
                ->setParameter('source', \mb_strtolower($filters['source']));
        }

        if (\array_key_exists('sourceHost', $filters)) {
            if (null === $filters['sourceHost']) {
                $queryBuilder->andWhere('redirect.sourceHost IS NULL');
            } else {
                $queryBuilder->andWhere('redirect.sourceHost = :sourceHost')
                    ->setParameter('sourceHost', \mb_strtolower($filters['sourceHost']));
            }
        }

        if (isset($filters['enabled'])) {
            $queryBuilder->andWhere('redirect.enabled = :enabled')
                ->setParameter('enabled', $filters['enabled']);
        }

        return $queryBuilder;
    }
}
