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
 * Repository for the Catalogues, implementing some additional functions
 * for querying objects.
 */
class CatalogueRepository extends EntityRepository
{
    /**
     * returns a catalogue with given ID.
     *
     * @param $id
     *
     * @return Catalogue|null
     */
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
            $query->setParameter('catalogueId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }
}
