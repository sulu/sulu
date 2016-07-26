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

use Composer\Script\Event;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler;

class TranslateScriptHandler extends ScriptHandler
{
    /**
     * @param $event Event A instance
     */
    public static function installTranslations(Event $event)
    {
        $options = parent::getOptions($event);
        $consoleDir = isset($options['symfony-bin-dir']) ? $options['symfony-bin-dir'] : $options['symfony-app-dir'];

        parent::executeCommand(
            $event,
            $consoleDir,
            'sulu:translate:export'
        );
    }
}
