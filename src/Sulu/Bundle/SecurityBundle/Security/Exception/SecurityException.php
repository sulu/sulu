<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

use Exception;

/**
 * Class SecurityException
 * This exception is a general security exception
 * Exceptions related with the security bundle should inherit form this exception and use it's exception codes
 * @package Sulu\Bundle\SecurityBundle\Security\Exception
 */
class SecurityException extends Exception
{
    /**
     * @var int
     * @description this exception code is thrown when the username is not unique
     */
    const EXCEPTION_CODE_USERNAME_NOT_UNIQUE = 1001;

    /**
     * @var int
     * @description this exception code is thrown when no password is provided in the post action of a user
     */
    const EXCEPTION_CODE_MISSING_PASSWORD = 1002;

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message
        );
    }
} 
