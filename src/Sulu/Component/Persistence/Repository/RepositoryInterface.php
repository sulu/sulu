<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Repository;

use Doctrine\Common\Persistence\ObjectRepository;

/**
 * Repository interface.
 */
interface RepositoryInterface extends ObjectRepository
{
    /**
     * Create a new instance of a model.
     *
     * @return mixed
     */
    public function createNew();
}
