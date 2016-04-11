<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Interface for checking Sulu specific permissions.
 */
interface SecurityCheckerInterface
{
    /**
     * Checks a Sulu specific permission based on the subject, a permission type and a locale.
     *
     * @param mixed  $subject
     * @param string $permission
     *
     * @return bool
     *
     * @throws AccessDeniedException
     */
    public function checkPermission($subject, $permission);

    /**
     * @param $subject
     * @param $permission
     *
     * @return bool
     */
    public function hasPermission($subject, $permission);
}
