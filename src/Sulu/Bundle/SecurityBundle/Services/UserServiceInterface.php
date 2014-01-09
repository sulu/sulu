<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Services;

use Sulu\Bundle\SecurityBundle\Entity\User;

interface UserServiceInterface
{
    /**
     * returns user for given id
     * @param integer $id userId
     * @return User
     */
    public function getUserById($id);

    /**
     * returns username by id
     * @param integer $id userId
     * @return string
     */
    public function getUsernameByUserId($id);

    /**
     * returns fullName for userId
     * @param integer $id userId
     * @return string
     */
    public function getFullNameByUserId($id);
} 
