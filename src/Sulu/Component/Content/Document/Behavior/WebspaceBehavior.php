<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * Document is contained within a webspace.
 */
interface WebspaceBehavior
{
    /**
     * Return the webspace name.
     */
    public function getWebspaceName();
}
