<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\ConnectionContext;

/**
 * Websocket context which implies logged in user in a specific firewall.
 */
interface AuthenticatedConnectionContextInterface extends ConnectionContextInterface
{
    /**
     * Returns user for the current firewall.
     *
     * @return null|\Sulu\Component\Security\Authentication\UserInterface
     */
    public function getFirewallUser();
}
