<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

use Sulu\Component\DocumentManager\Behavior\Path\BasePathBehavior;

/**
 * TODO: Document me.
 */
interface SyncronizeBehavior
{
    const SYNCED_FIELD = 'synced';

    /**
     * Return the names of all document managers to which this document is
     * synced to.
     *
     * @return string[]
     */
    public function getSyncronizedManagers();
}
