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
use Doctrine\ORM\NoResultException;

/**
 * Repository for the Packages, implementing some additional functions
 * for querying objects
 */
class CatalogueRepository extends EntityRepository
{
    public function getCatalogueById($id)
    {
        try {

            $qb = $this->createQueryBuilder('catalogue')
                ->leftJoin('catalogue.package', 'package')
                ->leftJoin('package.catalogues', 'catalogues')
                ->addSelect('package')
                ->addSelect('catalogues')
                ->where('catalogue.id=:catalogueId');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('catalogueId', $id);

            return $query->getSingleResult();

        } catch (NoResultException $ex) {
            return null;
        }
    }
}
