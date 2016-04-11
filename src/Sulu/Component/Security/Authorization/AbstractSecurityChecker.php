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
 * Implements an abstraction for the SecurityCheckerInterface, which needs the subclass to implement the
 * hasPermission method. This method will be called for the checkPermission method, which throws an exception,
 * if permission is not granted.
 */
abstract class AbstractSecurityChecker implements SecurityCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function checkPermission($subject, $permission)
    {
        if (!$this->hasPermission($subject, $permission)) {
            if ($subject instanceof SecurityCondition) {
                $message = sprintf(
                    'Permission "%s" in localization "%s" for object with id "%s" and of type "%s" not granted',
                    $permission,
                    $subject->getLocale(),
                    $subject->getObjectId(),
                    $subject->getObjectType()
                );
            } else {
                $message = sprintf('Permission "%s" in security context "%s" not granted', $permission, $subject);
            }

            throw new AccessDeniedException($message);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function hasPermission($subject, $permission);
}
