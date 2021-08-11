<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

/**
 * Enumeration for all available permission types in Sulu.
 */
final class PermissionTypes
{
    public const VIEW = 'view';

    public const ADD = 'add';

    public const EDIT = 'edit';

    public const DELETE = 'delete';

    public const ARCHIVE = 'archive';

    public const LIVE = 'live';

    public const SECURITY = 'security';
}
