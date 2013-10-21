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
    public static function installAppAssets(CommandEvent $event)
    {
        $options = parent::getOptions($event);
        $appDir = $options['symfony-app-dir'];
        $webDir = $options['symfony-web-dir'];

        $symlink = '';
        if ($options['symfony-assets-install'] == 'symlink') {
            $symlink = '--symlink ';
        } elseif ($options['symfony-assets-install'] == 'relative') {
            $symlink = '--symlink --relative ';
        }

        if (!is_dir($webDir)) {
            echo 'The symfony-web-dir (' . $webDir . ') specified in composer.json was not found in ' . getcwd(
                ) . ', can not install assets.' . PHP_EOL;

            return;
        }

        parent::executeCommand($event, $appDir, 'assets:install ' . $symlink . escapeshellarg($webDir));
    }
}
