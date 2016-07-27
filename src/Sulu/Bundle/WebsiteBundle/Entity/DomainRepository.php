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
use Doctrine\ORM\NoResultException;

/**
 * Repository for domains.
 */
class DomainRepository extends EntityRepository
{
    /**
     * Returns domain with given url and environment.
     *
     * @param string $url
     * @param string $environment
     *
     * @return Domain
     */
    public function findByUrlAndEnvironment($url, $environment)
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->where('u.url = :url')
            ->andWhere('u.environment = :environment');

        $query = $queryBuilder->getQuery();
        $query->setParameter('url', $url);
        $query->setParameter('environment', $environment);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }
}
