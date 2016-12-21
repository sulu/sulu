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
 * Adds the user which is defined as author and an authored date.
 */
interface AuthorBehavior
{
    /**
     * Returns author-date.
     *
     * @return string
     */
    public function getAuthored();

    /**
     * Set author-date.
     *
     * @param string $authored
     *
     * @return $this
     */
    public function setAuthored($authored);

    /**
     * Returns array of author contact ids.
     *
     * @return int[]
     */
    public function getAuthors();

    /**
     * Set array of author contact ids.
     *
     * @param int[] $authors
     *
     * @return $this
     */
    public function setAuthors(array $authors);
}
