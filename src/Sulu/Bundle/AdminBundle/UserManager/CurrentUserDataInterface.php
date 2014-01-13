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

interface CurrentUserDataInterface
{
    /**
     * returns if user is admin user
     * @return Boolean
     */
    public function isAdminUser();

    /**
     * returns if a user is logged in
     * @return Boolean
     */
    public function isLoggedIn();

    /**
     * returns username
     * @return String
     */
    public function getUsername();

    /**
     * returns fullName
     * @return String
     */
    public function getFullName();

    /**
     * returns UserIcon URL
     * @return String
     */
    public function getUserIcon();

    /**
     * returns Logout URL
     * @return String
     */
    public function getLogoutLink();

    /**
     * returns locale of current user
     * @return String
     */
    public function getLocale();
} 
