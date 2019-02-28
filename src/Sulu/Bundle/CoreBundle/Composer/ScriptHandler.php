<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Composer;

/**
 * A handler for general tasks executed with composer scripts.
 */
class ScriptHandler
{
    const GIT_IGNORE_FILE = '.gitignore';

    /**
     * Removes the composer.lock file from .gitignore, because we don't want the composer.lock to be included in our
     * repositories, but they should be included when developing specific project.
     */
    public static function removeComposerLockFromGitIgnore()
    {
        if (!file_exists(static::GIT_IGNORE_FILE)) {
            return;
        }

        $gitignore = file_get_contents(static::GIT_IGNORE_FILE);
        $gitignore = str_replace("composer.lock\n", '', $gitignore);
        file_put_contents(static::GIT_IGNORE_FILE, $gitignore);
    }
}
