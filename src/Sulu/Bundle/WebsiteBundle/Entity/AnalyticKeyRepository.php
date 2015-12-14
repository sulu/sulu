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
 * Repository for analytic keys.
 */
class AnalyticKeyRepository extends EntityRepository
{
    /**
     * Returns list of analytic-keys.
     *
     * @param $webspaceKey
     *
     * @return AnalyticKey[]
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
     * @return AnalyticKey
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
}
