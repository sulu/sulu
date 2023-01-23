<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Model;

use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Classes implementing this interface must ensure they keep track of users that create and update it.
 */
interface UserBlameInterface
{
    /**
     * Return the user that created this object.
     *
     * @return UserInterface|null
     */
    public function getCreator();

    /**
     * Return the user that change this object the last time.
     * this object.
     *
     * @return UserInterface|null
     */
    public function getChanger();
}
