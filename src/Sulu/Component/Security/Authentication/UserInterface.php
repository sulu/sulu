<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

/**
 * The UserInterface for Sulu, extends the Symfony UserInterface with an ID.
 */
interface UserInterface extends \Symfony\Component\Security\Core\User\UserInterface
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
}
