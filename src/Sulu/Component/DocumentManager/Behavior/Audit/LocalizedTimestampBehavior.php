<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior\Audit;

/**
 * Adds the date when created and lastly changed the document.
 */
interface LocalizedTimestampBehavior
{
    /**
     * @return \DateTime
     */
    public function getCreated();

    /**
     * @return \DateTime
     */
    public function getChanged();
}
