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

/**
 * This Exception is thrown if an invalid email-address is passed to the api
 * @package Sulu\Bundle\SecurityBundle\Security\Exception
 */
class InvalidEmailException extends SecurityException
{
    public function __construct()
    {
        parent::__construct('security.user.error.invalidEmail', 1003);
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message
        );
    }
}
