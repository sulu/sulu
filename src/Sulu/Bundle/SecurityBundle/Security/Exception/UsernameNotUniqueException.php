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


class UsernameNotUniqueException extends SecurityException
{
    /**
     * The username which is not unique
     * @var integer
     */
    private $username;

    public function __construct($username)
    {
        parent::__construct('security.user.error.notUnique', self::EXCEPTION_CODE_USERNAME_NOT_UNIQUE);
        $this->username = $username;
    }

    public function getUsername(){
        return $this->username;
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'username' => $this->username
        );
    }
}
