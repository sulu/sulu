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

namespace Sulu\CustomUrl\Domain\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\CustomUrl\Domain\Exception\MismatchingDomainPartException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRouteRepositoryInterface;

class CustomUrlRouteRepository implements CustomUrlRouteRepositoryInterface
{
    /**
     * @var EntityRepository<CustomUrlRoute>
     */
    private EntityRepository $repository;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(CustomUrlRoute::class);
    }

    public function count(): int
    {
        return $this->repository->count([]);
    }

    public function findByCustomUrl(CustomUrlInterface $customUrl): array
    {
        return $this->repository->findBy(['customUrl' => $customUrl]);
    }

    public function findHistoryRoutes(CustomUrlInterface|string $customUrl): array
    {
        /** @var array{id: string, resourcelocator: string, created: \DateTimeInterface} $historicRoutes */
        $historicRoutes = $this->repository
            ->createQueryBuilder('r')
            ->select(['r.uuid as id', 'r.path as resourcelocator', 'r.created'])
            ->andWhere('r.customUrl = :customUrl')
            ->orderBy('r.created', 'DESC')
            ->setParameter('customUrl', $customUrl)
            ->setFirstResult(1)
            ->getQuery()
            ->getArrayResult()
        ;

        return $historicRoutes;
    }

    public function addRoute(CustomUrlInterface $customUrl): void
    {
        $newUrl = '';
        $domainParts = \array_map(
            static fn (string $domainPart): string => \str_replace('*', '-', $domainPart),
            $customUrl->getDomainParts()
        );
        $baseDomain = $customUrl->getBaseDomain();
        for ($i = 0; $i < \strlen($baseDomain); ++$i) {
            if ('*' === $baseDomain[$i]) {
                $nextValue = \array_shift($domainParts);
                if (null === $nextValue) {
                    throw new MismatchingDomainPartException($baseDomain, $customUrl->getDomainParts());
                }
                $newUrl .= $nextValue;
            } else {
                $newUrl .= $baseDomain[$i];
            }
        }

        if ([] !== $domainParts) {
            throw new MismatchingDomainPartException($baseDomain, $customUrl->getDomainParts());
        }

        $existingRoute = $this->repository->findOneBy(['customUrl' => $customUrl, 'path' => $newUrl]);
        if (null !== $existingRoute) {
            $existingRoute->setCreated(new \DateTimeImmutable());

            return;
        }

        $this->entityManager->persist(new CustomUrlRoute(
            customUrl: $customUrl,
            path: $newUrl,
        ));
    }

    public function deleteAll(array $ids, string $webspaceKey): void
    {
        $this->repository
            ->createQueryBuilder('r')
            ->delete()
            ->join('r.customUrl', 'c')
            ->where('id IN (:ids)')
            ->andWhere('c.webspace = :webspace')
            ->setParameter('webspace', $webspaceKey)
            ->setParameter('ids', $ids)
        ;
    }
}
