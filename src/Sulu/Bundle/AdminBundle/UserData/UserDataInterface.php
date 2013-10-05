<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\AdminBundle\UserData;


interface UserDataInterface
{

    /**
     * @return Boolean - returns if user is admin user
     */
    public function isAdminUser();

    /**
     * @return Boolean - returns if a user is logged in
     */
    public function isLoggedIn();


    /**
     * @return String - returns username
     */
    public function getUserName();

    /**
     * @return String - returns UserIcon URL
     */
    public function getUserIcon();

    /**
     * @return String - returns Logout URL
     */
    public function getLogoutLink();


    // public function getUserRole();

}
