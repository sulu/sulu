<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\AdminBundle\UserManager;

interface UserManagerInterface
{
    /**
     * returns user data of current user
     * @return UserDataInterface
     */
    public function getCurrentUserData();

    /**
     * returns username for given id
     * @param integer $id userId
     * @return string
     */
    public function getUsernameByUserId($id);

    /**
     * returns fullName for given id
     * @param integer $id userId
     * @return string
     */
    public function getFullNameByUserId($id);

}
