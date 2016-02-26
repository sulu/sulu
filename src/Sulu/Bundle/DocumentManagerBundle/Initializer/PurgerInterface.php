<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

/**
 * Classes implementing this interface are respoinsible for purging
 * ALL the configured sessions of the content repository.
 *
 * The content repository should be flushed after purging.
 */
interface PurgerInterface
{
    /**
     * Purge the configured sessions.
     */
    public function purge();
}
