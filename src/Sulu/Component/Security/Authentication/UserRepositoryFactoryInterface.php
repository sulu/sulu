<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

interface UserRepositoryFactoryInterface
{
    /**
     * Returns the user repository for the given system.
     *
     * @return UserRepositoryInterface
     */
    public function getRepository();
}
