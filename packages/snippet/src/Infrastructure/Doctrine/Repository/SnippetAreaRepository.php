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
use Doctrine\ORM\QueryBuilder;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;

class SnippetAreaRepository implements SnippetAreaRepositoryInterface
{
    /**
     * @var EntityRepository<SnippetAreaInterface>
     */
    private EntityRepository $entityRepository;

    private string $snippetAreaClassName;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->entityRepository = $this->entityManager->getRepository(SnippetAreaInterface::class);
        $this->snippetAreaClassName = $this->entityRepository->getClassName();
    }

    /**
     * @param array{webspaceKey?: string} $filters
     */
    private function createQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('area')
            ->join('area.snippet', 'snippet');

        $webspaceKey = $filters['webspaceKey'] ?? null;
        if (null !== $webspaceKey) {
            $queryBuilder->andWhere('area.webspaceKey = :webspaceKey')
                ->setParameter('webspaceKey', $webspaceKey);
        }

        return $queryBuilder;
    }

    /**
     * @return array<string, SnippetAreaInterface>
     */
    public function findByWebspace(string $webspaceKey): array
    {
        $result = [];

        /** @var array<SnippetAreaInterface> $queryResult */
        $queryResult = $this->createQueryBuilder(['webspaceKey' => $webspaceKey])
            ->getQuery()
            ->getResult()
        ;

        foreach ($queryResult as $row) {
            $result[$row->getAreaKey()] = $row;
        }

        return $result;
    }

    public function createNew(string $areaKey, string $webspaceKey, ?string $uuid = null): SnippetAreaInterface
    {
        /** @var class-string<SnippetAreaInterface> $className */
        $className = $this->snippetAreaClassName;

        return new $className($areaKey, $webspaceKey, $uuid);
    }

    public function findOneByWebspaceAndKey(string $webspaceKey, string $areaKey): ?SnippetAreaInterface
    {
        return $this->entityRepository->findOneBy(['webspaceKey' => $webspaceKey, 'areaKey' => $areaKey]);
    }

    public function add(SnippetAreaInterface $snippetArea): void
    {
        $this->entityManager->persist($snippetArea);
    }

    public function remove(SnippetAreaInterface $snippetArea): void
    {
        $this->entityManager->remove($snippetArea);
    }
}
