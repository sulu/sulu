<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;

class TagRepository extends EntityRepository implements TagRepositoryInterface
{
    /**
     * Finds the tag with the given ID.
     *
     * @param $id
     *
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
            return;
        }
    }

    /**
     * Finds the tag with the given name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function findTagByName($name)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.name = :name');

        $query = $qb->getQuery();
        $query->setParameter('name', $name);

        try {
            $tag = $query->getSingleResult();

            return $tag;
        } catch (NoResultException $nre) {
            return;
        }
    }

    /**
     * Searches for all roles.
     *
     * @return array
     */
    public function findAllTags()
    {
        try {
            $qb = $this->createQueryBuilder('t');

            $query = $qb->getQuery();

            $result = $query->getResult();

            return $result;
        } catch (NoResultException $ex) {
            return;
        }
    }
}
