<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Security\Event;

final class SecurityEvents
{
    /**
     * The permission.update event is thrown when the AccessControlManager has updated some permissions.
     * The event listener receives a PermissionUpdateEvent.
     */
    const PERMISSION_UPDATE = 'sulu.security.permission.update';
}
