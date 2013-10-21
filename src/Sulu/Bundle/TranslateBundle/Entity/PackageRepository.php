<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\TranslateBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * Repository for the Packages, implementing some additional functions
 * for querying objects
 */
class PackageRepository extends EntityRepository
{
    public function getPackageById($id) {
        $qb = $this->createQueryBuilder('package')
            ->leftJoin('package.catalogues', 'catalogues')
            ->addSelect('catalogues')
            ->where('package.id=:packageId');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $query->setParameter('packageId', $id);

        return $query->getSingleResult();
    }
}
