<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Model;

/**
 * Classes implemting this interface must ensure they keep track
 * of uses that create and update it.
 */
interface UserBlameInterface
{
    /**
     * Return the ID of the user that created this object.
     *
     * @return \DateTime
     */
    public function getCreator();

    /**
     * Return the ID of the last user to change
     * this object.
     *
     * @return \DateTime
     */
    public function getChanger();
}
