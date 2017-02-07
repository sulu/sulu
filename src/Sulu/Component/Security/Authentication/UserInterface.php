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

use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;

/**
 * The UserInterface for Sulu, extends the Symfony UserInterface with an ID.
 */
interface UserInterface extends BaseUserInterface
{
    /**
     * Returns the ID of the User.
     *
     * @return int
     */
    public function getId();

    /**
     * Returns the locale of the user.
     *
     * @return string Users locale
     */
    public function getLocale();

    /**
     * Returns all the roles the user has assigned.
     *
     * @return RoleInterface[]
     */
    public function getRoleObjects();

    /**
     * Returns the full name of the user.
     *
     * @return string
     */
    public function getFullName();

    /**
     * Get locked.
     *
     * @return bool
     */
    public function getLocked();

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled();
}
