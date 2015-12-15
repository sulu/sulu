<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for analytics.
 */
class AnalyticRepository extends EntityRepository
{
    /**
     * Returns list of analytics.
     *
     * @param $webspaceKey
     *
     * @return Analytic[]
     */
    public function findByWebspaceKey($webspaceKey)
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('domains')
            ->leftJoin('a.domains', 'domains')
            ->where('a.webspaceKey = :webspaceKey');

        $query = $queryBuilder->getQuery();
        $query->setParameter('webspaceKey', $webspaceKey);

        return $query->getResult();
    }

    /**
     * Returns analytic-key by id.
     *
     * @param int $id
     *
     * @return Analytic
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
     * Returns analytic-key by url.
     *
     * @param string $urlExpression
     *
     * @return Analytic[]
     */
    public function findByUrl($urlExpression)
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('domains')
            ->leftJoin('a.domains', 'domains')
            ->where('a.allDomains = 1')
            ->orwhere('domains.url= :url');

        $query = $queryBuilder->getQuery();
        $query->setParameter('url', $urlExpression);

        return $query->getResult();
    }
}
