<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * Repository for the Packages, implementing some additional functions
 * for querying objects.
 */
class PackageRepository extends EntityRepository
{
    /**
     * returns a package with given ID.
     *
     * @param $id
     *
     * @return Package|null
     */
    public function getPackageById($id)
    {
        try {
            $qb = $this->createQueryBuilder('package')
                ->leftJoin('package.catalogues', 'catalogues')
                ->addSelect('catalogues')
                ->where('package.id=:packageId');

            $query = $qb->getQuery();
            $query->setParameter('packageId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * returns a package for a given name.
     *
     * @param $name
     *
     * @return Package|null
     */
    public function getPackageByName($name)
    {
        try {
            $qb = $this->createQueryBuilder('package')
                ->leftJoin('package.catalogues', 'catalogues')
                ->leftJoin('package.codes', 'codes')
                ->leftJoin('catalogues.translations', 'translations')
                ->leftJoin('translations.code', 'code')
                ->where('package.name=:packageName');

            $query = $qb->getQuery();
            $query->setParameter('packageName', $name);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }
}
