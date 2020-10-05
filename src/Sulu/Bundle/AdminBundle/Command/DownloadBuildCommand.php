<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

@\trigger_error(
    \sprintf(
        'The "%s" class is deprecated since Sulu 2, use "%s" instead.',
        DownloadBuildCommand::class,
        UpdateBuildCommand::class
    ),
    \E_USER_DEPRECATED
);

/**
 * @deprecated use the "UpdateBuildCommand" class instead
 */
class DownloadBuildCommand extends UpdateBuildCommand
{
    protected static $defaultName = 'sulu:admin:download-build';
}
