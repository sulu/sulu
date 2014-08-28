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


class MissingPasswordException extends SecurityException
{
    public function __construct()
    {
        parent::__construct('security.user.error.missingPassword', self::EXCEPTION_CODE_MISSING_PASSWORD);
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message
        );
    }
}
