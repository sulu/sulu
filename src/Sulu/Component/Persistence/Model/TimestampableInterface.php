<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Model;

/**
 * Classes implementing this interface must keep track of when they were created and updated.
 */
interface TimestampableInterface
{
    /**
     * Return the date the object implementing this inteface
     * was created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Return the date the object implementing this inteface
     * was changed.
     *
     * @return \DateTime
     */
    public function getChanged();
}
