<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Composer;

use Composer\Script\CommandEvent;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler;

class SuluScriptHandler extends ScriptHandler
{
    /**
     * @param $event CommandEvent A instance
     */
    public static function installApp(CommandEvent $event)
    {
        $options = parent::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        $dir = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))));

        parent::executeCommand(
            $event,
            $appDir,
            'sulu:install:kernel ' . escapeshellarg($dir . '/' . $appDir) . ' website'
        );
    }
}
