<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Composer;

use Composer\Script\CommandEvent;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler;

class MediaScriptHandler extends ScriptHandler
{
    /**
     * @param $event CommandEvent A instance
     */
    public static function initBundle(CommandEvent $event)
    {
        $options = parent::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        parent::executeCommand(
            $event,
            $appDir,
            'sulu:media:init'
        );
    }
}
