<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior\Mapping;

/**
 * Populate the UUID.
 */
interface UuidBehavior
{
    /**
     * Return the documents UUID.
     *
     * @return string
     */
    public function getUuid();
}
