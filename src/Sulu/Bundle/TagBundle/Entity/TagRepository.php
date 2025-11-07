<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Entity;

use Doctrine\ORM\NoResultException;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

/**
 * @extends EntityRepository<TagInterface>
 */
class TagRepository extends EntityRepository implements TagRepositoryInterface
{
    public function findTagById($id)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.id = :id');

        $query = $qb->getQuery();
        $query->setParameter('id', $id);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $nre) {
            return null;
        }
    }

    public function findTagByName($name)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.name = :name');

        $query = $qb->getQuery();
        $query->setParameter('name', $name);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $nre) {
            return null;
        }
    }

    /**
     * @deprecated use the Repository findAll method
     */
    public function findAllTags()
    {
        return $this->findAll();
    }
}
