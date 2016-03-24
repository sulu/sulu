<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Templating;

/**
 * Container for engine events emitted by the eventable-engine.
 */
final class EngineEvents
{
    /**
     * Will be emitted once in a request - right before the first render is called.
     */
    const INITIALIZE = 'engine.initialize';
}
