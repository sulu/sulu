<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\Session;

use PHPCR\SessionInterface;

interface SessionServiceInterface
{
    /**
     * returns a valid session to interact with a phpcr database
     * @param string $key
     * @return SessionInterface
     */
    public function getSession($key = 'default');
}
