<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Entity;

use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

/**
 * Repository for analytics.
 */
class AnalyticsRepository extends EntityRepository implements AnalyticsRepositoryInterface
{
    public function findByWebspaceKey(string $webspaceKey, string $environment): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('domains')
            ->leftJoin('a.domains', 'domains')
            ->where('a.webspaceKey = :webspaceKey')
            ->andWhere('a.allDomains = TRUE OR domains.environment = :environment')
            ->orderBy('a.id', 'ASC');

        $query = $queryBuilder->getQuery();
        $query->setParameter('webspaceKey', $webspaceKey);
        $query->setParameter('environment', $environment);

        return $query->getResult();
    }

    public function findById(int $id): AnalyticsInterface
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('domains')
            ->leftJoin('a.domains', 'domains')
            ->where('a.id = :id');

        $query = $queryBuilder->getQuery();
        $query->setParameter('id', $id);

        return $query->getSingleResult();
    }

    public function findByUrl(string $url, string $webspaceKey, string $environment): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('domains')
            ->leftJoin('a.domains', 'domains')
            ->where('a.allDomains = TRUE OR (domains.url = :url AND domains.environment = :environment)')
            ->andWhere('a.webspaceKey = :webspaceKey')
            ->orderBy('a.id', 'ASC');

        $query = $queryBuilder->getQuery();
        $query->setParameter('url', $url);
        $query->setParameter('webspaceKey', $webspaceKey);
        $query->setParameter('environment', $environment);

        return $query->getResult();
    }
}
