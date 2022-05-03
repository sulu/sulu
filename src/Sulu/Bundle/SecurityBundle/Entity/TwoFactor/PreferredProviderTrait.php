<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity\TwoFactor;

use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;

/*
 * Bridge trait to the scheb/2fa-bundle PreferredProviderInterface.
 */
if (\interface_exists(PreferredProviderInterface::class)) {
    /**
     * @internal
     */
    trait PreferredProviderTrait
    {
        public function getPreferredTwoFactorProvider(): ?string
        {
            return $this->twoFactorType;
        }
    }
} else {
    /**
     * @internal
     */
    trait PreferredProviderTrait
    {
    }
}
