<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

/**
 * Utility functions of UUID
 * @package Sulu\Component\Util
 */
class UuidUtils
{
    /**
     * checks if given string is a valid UUID
     * @param $id
     * @return bool
     */
    public static function isUUID($id)
    {
        $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

        return preg_match($pattern, $id);
    }
}
