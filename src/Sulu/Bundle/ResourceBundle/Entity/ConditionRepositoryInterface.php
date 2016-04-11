<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * The interface for the condition entity repository
 * Interface ConditionRepositoryInterface.
 */
interface ConditionRepositoryInterface
{
    /**
     * Finds an entity by id.
     *
     * @param $id
     *
     * @return mixed
     */
    public function findById($id);
}
