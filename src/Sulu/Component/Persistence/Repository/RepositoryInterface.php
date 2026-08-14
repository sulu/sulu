<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Repository;

use Doctrine\Persistence\ObjectRepository;

/**
 * @deprecated since Sulu 2.6, we recommend building your own repository interface instead of using this.
 *
 * Repository interface.
 *
 * @template T of object
 *
 * @extends ObjectRepository<T>
 */
interface RepositoryInterface extends ObjectRepository
{
    /**
     * Create a new instance of a model.
     *
     * @return T
     */
    public function createNew();
}
