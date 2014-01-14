<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Entity;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class TagRepository extends EntityRepository
{
    /**
     * Finds the tag with the given ID
     * @param $id
     * @return Tag
     */
    public function findTagById($id)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.id = :id');

        $query = $qb->getQuery();
        $query->setParameter('id', $id);

        try {
            $tag = $query->getSingleResult();

            return $tag;
        } catch (NoResultException $nre) {
            return null;
        }
    }
} 
