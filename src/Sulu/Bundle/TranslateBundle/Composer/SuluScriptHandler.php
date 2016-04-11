<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Composer;

use Composer\Script\CommandEvent;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler;

class SuluScriptHandler extends ScriptHandler
{
    /**
     * @param $event CommandEvent A instance
     */
    public static function installTranslations(CommandEvent $event)
    {
        $options = parent::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        parent::executeCommand($event, $appDir, 'sulu:translate:import en');
        parent::executeCommand($event, $appDir, 'sulu:translate:import de');
        parent::executeCommand($event, $appDir, 'sulu:translate:export en json');
        parent::executeCommand($event, $appDir, 'sulu:translate:export de json');
    }
}
