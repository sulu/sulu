<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

/**
 * Container for preview event names.
 */
final class Events
{
    /**
     * Will be raised right before preview rendering.
     */
    const PRE_RENDER = 'sulu.preview.pre-render';

    /**
     * No object can be created for this class.
     */
    private function __construct()
    {
    }
}
