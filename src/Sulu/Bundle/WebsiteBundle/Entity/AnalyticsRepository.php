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

use Doctrine\ORM\EntityRepository;

/**
 * Repository for analytics.
 */
class AnalyticsRepository extends EntityRepository
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns list of analytics filterd by webspace key and environment.
     *
     * @param string $webspaceKey
     *
     * @return Analytics[]
     */
    public function findByWebspaceKey($webspaceKey)
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('domains')
            ->leftJoin('a.domains', 'domains')
            ->where('a.webspaceKey = :webspaceKey')
            ->andWhere('a.allDomains = TRUE OR domains.environment = :environment')
            ->orderBy('a.id', 'ASC');

        $query = $queryBuilder->getQuery();
        $query->setParameter('webspaceKey', $webspaceKey);
        $query->setParameter('environment', $this->environment);

        return $query->getResult();
    }

    /**
     * Returns analytics by id.
     *
     * @param int $id
     *
     * @return Analytics
     */
    public function findById($id)
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('domains')
            ->leftJoin('a.domains', 'domains')
            ->where('a.id = :id');

        $query = $queryBuilder->getQuery();
        $query->setParameter('id', $id);

        return $query->getSingleResult();
    }

    /**
     * Returns analytics by url.
     *
     * @param string $url
     * @param string $webspaceKey
     * @param string $environment
     *
     * @return Analytics[]
     */
    public function findByUrl($url, $webspaceKey, $environment)
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
