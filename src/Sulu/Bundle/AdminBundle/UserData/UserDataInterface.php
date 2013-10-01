<?php

namespace Sulu\Bundle\AdminBundle\UserData;


interface UserDataInterface {

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