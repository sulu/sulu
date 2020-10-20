<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AuditBundle\Repository;

use Sulu\Bundle\AuditBundle\Entity\Trail;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class TrailRepository extends EntityRepository implements TrailRepositoryInterface
{
    public function findTrailById(int $id)
    {
        // TODO: Implement findTrailById() method.
    }

    public function findAllTrails()
    {
        // TODO: Implement findAllTrails() method.
    }

    public function save(Trail $trail)
    {
        $this->_em->persist($trail);
        $this->_em->flush();
    }
}
