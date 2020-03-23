<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Doctrine\SQLite;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

/**
 * @internal
 */
class ForeignKeyActivationSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::postConnect,
        ];
    }

    public function postConnect(ConnectionEventArgs $args)
    {
        if ('sqlite' !== strtolower($args->getConnection()->getDatabasePlatform()->getName())) {
            return;
        }

        $args->getConnection()->exec('PRAGMA foreign_keys = ON;');
    }
}
