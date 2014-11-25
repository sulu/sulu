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
 * Implements an abstraction for the SecurityCheckerInterface, which needs the subclass to implement the
 * hasPermission method. This method will be called for the checkPermission method, which throws an exception,
 * if permission is not granted.
 * @package Sulu\Bundle\SecurityBundle\Permission
 */
abstract class AbstractSecurityChecker implements SecurityCheckerInterface
{
    /**
     * {@inheritDoc}
     */
    public function checkPermission($subject, $permission, $locale = null)
    {
        if (!$this->hasPermission($subject, $permission, $locale)) {
            throw new AccessDeniedException(
                sprintf('Permission "%s" in localization "%s" not granted', $permission, $locale)
            );
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public abstract function hasPermission($subject, $permission, $locale = null);
}
