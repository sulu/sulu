<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Defines the interface for a UserRepository
 * @package Sulu\Component\Security
 */
interface UserRepositoryInterface extends UserProviderInterface
{
    /**
     * Sets the security system
     * @param string $system
     */
    public function setSystem($system);

    /**
     * Returns the security system
     * @return string
     */
    public function getSystem();

    /**
     * Returns the user with the given id
     * @param int $id The user to find
     * @return UserInterface
     */
    public function findUserById($id);
} 
