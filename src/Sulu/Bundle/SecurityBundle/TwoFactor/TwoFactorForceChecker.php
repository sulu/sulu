<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\TwoFactor;

use Sulu\Bundle\SecurityBundle\Controller\ProfileTwoFactorController;
use Sulu\Bundle\SecurityBundle\Entity\User;

/**
 * @internal
 */
class TwoFactorForceChecker
{
    /**
     * @param string[] $availableMethods
     */
    public function __construct(
        private ?string $forcePattern,
        private bool $setupEnabled,
        private array $availableMethods,
    ) {
    }

    public function isForced(User $user): bool
    {
        if (!$this->forcePattern) {
            return false;
        }

        return (bool) \preg_match($this->forcePattern, $user->getEmail() ?: '');
    }

    /**
     * The user is forced to use a second factor but did not activate a usable method yet,
     * so the setup has to be completed before the admin can be used.
     */
    public function isSetupRequired(User $user): bool
    {
        if (!$this->setupEnabled || !$this->isForced($user)) {
            return false;
        }

        return !$this->isConfigured($user);
    }

    private function isConfigured(User $user): bool
    {
        $twoFactor = $user->getTwoFactor();
        $method = $twoFactor?->getMethod();

        // a method that is not available anymore does not authenticate anybody, so it counts as
        // not configured and the user has to pick one of the remaining methods
        if (!$method || !\in_array($method, $this->availableMethods, true)) {
            return false;
        }

        $secretOption = ProfileTwoFactorController::SECRET_OPTIONS[$method]['secret'] ?? null;
        if (!$secretOption) {
            return true;
        }

        return (bool) ($twoFactor->getOptions()[$secretOption] ?? null);
    }
}
