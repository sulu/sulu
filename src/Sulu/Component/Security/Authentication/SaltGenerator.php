<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

/**
 * A simple class for creating random salts for users.
 */
class SaltGenerator
{
    /**
     * Returns a random salt for password hashing.
     *
     * @return string
     */
    public function getRandomSalt()
    {
        return base64_encode(random_bytes(32));
    }
}
