<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Repository\ORM;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Doctrine ORM entity repository.
 *
 * @template T of object
 *
 * @extends BaseEntityRepository<T>
 *
 * @implements RepositoryInterface<T>
 */
class EntityRepository extends BaseEntityRepository implements RepositoryInterface
{
    /**
     * @return T
     */
    public function createNew()
    {
        $className = $this->getClassName();

        return new $className();
    }
}
