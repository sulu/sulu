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
 * Document can have a human-friendly title.
 */
interface TitleBehavior
{
    /**
     * Return a title.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set the title.
     *
     * @param string $title
     */
    public function setTitle($title);
}
