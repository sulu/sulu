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

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

/*
 * Bridge interface to the scheb/2fa-email TwoFactorInterface.
 */
if (\interface_exists(TwoFactorInterface::class)) {
    /**
     * @internal
     */
    interface EmailInterface extends TwoFactorInterface
    {
    }
} else {
    /**
     * @internal
     */
    interface EmailInterface
    {
    }
}
