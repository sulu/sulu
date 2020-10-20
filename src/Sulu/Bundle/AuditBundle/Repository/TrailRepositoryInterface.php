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
use Sulu\Bundle\AuditBundle\Entity\TrailInterface;

interface TrailRepositoryInterface
{
    /**
     * Finds the Trail with the given ID.
     *
     * @return TrailInterface
     */
    public function findTrailById(int $id);

    public function save(Trail $trail);
}
