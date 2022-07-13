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

use Scheb\TwoFactorBundle\Model\PreferredProviderInterface as SchebPreferredProviderInterface;

/*
 * Bridge interface to the scheb/2fa-bundle PreferredProviderInterface.
 */
if (\interface_exists(SchebPreferredProviderInterface::class)) {
    /*
     * @internal
     */
    \class_alias(SchebPreferredProviderInterface::class, PreferredProviderInterface::class);
} else {
    /**
     * @internal
     */
    interface PreferredProviderInterface
    {
    }
}
