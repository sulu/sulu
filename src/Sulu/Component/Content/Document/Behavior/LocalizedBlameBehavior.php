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

/**
 * Adds the user who created and lastly changed the document.
 */
interface LocalizedBlameBehavior
{
    /**
     * @return int
     */
    public function getCreator();

    /**
     * @return int
     */
    public function getChanger();
}
