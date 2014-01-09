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

    public function getCurrentUserData();

    public function getUserById($id);

    public function getUsernameByUserId($id);

    public function getFullNameByUserId($id);

}
