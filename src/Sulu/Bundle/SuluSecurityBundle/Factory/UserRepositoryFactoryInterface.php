<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Factory;

use Sulu\Component\Security\UserRepositoryInterface;

interface UserRepositoryFactoryInterface
{
    /**
     * Returns the user repository for the given system
     * @return UserRepositoryInterface
     */
    public function getRepository();
} 
