<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Permission;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Interface for checking Sulu specific permissions
 * @package Sulu\Bundle\SecurityBundle\Permission
 */
interface SecurityCheckerInterface
{
    /**
     * Checks a Sulu specific permission based on the subject, a permission type and a locale
     * @param mixed $subject
     * @param string $permission
     * @param string|null $locale
     * @return boolean
     * @throws AccessDeniedException
     */
    public function checkPermission($subject, $permission, $locale = null);

    /**
     * @param $subject
     * @param $permission
     * @param string|null $locale
     * @return boolean
     */
    public function hasPermission($subject, $permission, $locale = null);
} 
