<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Composer;

use Composer\Script\Event;

/**
 * A handler for general tasks executed with composer scripts.
 */
class ScriptHandler
{
    const GIT_IGNORE_FILE = '.gitignore';

    const ENV_FILE = '.env';

    const ENV_DIST_FILE = '.env.dist';

    /**
     * Copy the .env.dist file to .env file if not exists and generate a app secret.
     */
    public static function copyEnvDistFile(Event $event)
    {
        if (file_exists(static::ENV_FILE) || !file_exists(static::ENV_DIST_FILE)) {
            return;
        }

        $envFile = file_get_contents(static::ENV_DIST_FILE);
        $envFile = str_replace('APP_SECRET=', 'APP_SECRET=' . bin2hex(random_bytes(16)), $envFile);
        file_put_contents(static::ENV_FILE, $envFile);

        $event->getIO()->write('Please edit the created ".env" file to match your settings.');
    }

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

    /**
     * Removes the package-lock.json file from .gitignore, because we don't want the package-lock.json to be included in
     * our repositories, but they should be included when developing specific project.
     */
    public static function removePackageLockJsonFromGitIgnore()
    {
        if (!file_exists(static::GIT_IGNORE_FILE)) {
            return;
        }

        $gitignore = file_get_contents(static::GIT_IGNORE_FILE);
        $gitignore = str_replace("package-lock.json\n", '', $gitignore);
        file_put_contents(static::GIT_IGNORE_FILE, $gitignore);
    }
}
