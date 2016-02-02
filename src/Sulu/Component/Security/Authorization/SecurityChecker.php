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

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Implementation of Sulu specific security checks, includes a subject, the type of permission and the localization.
 */
class SecurityChecker extends AbstractSecurityChecker
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission($subject, $permission)
    {
        if (!$subject || !$this->tokenStorage->getToken()) {
            // if there is no subject the operation is allowed, since we have nothing to check against
            // if there is no token we are not behind a firewall, so the action is also allowed (e.g. command execution)
            return true;
        }

        $attributes = [$permission];

        if (is_string($subject)) {
            $subject = new SecurityCondition($subject);
        }

        $granted = $this->authorizationChecker->isGranted($attributes, $subject);

        return $granted;
    }
}
