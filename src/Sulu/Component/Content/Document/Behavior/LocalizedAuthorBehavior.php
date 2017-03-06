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
interface LocalizedAuthorBehavior extends LocalizedBlameBehavior
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
     */
    public function setAuthored($authored);

    /**
     * Returns id of author contact.
     *
     * @return int
     */
    public function getAuthor();

    /**
     * Set id of author contact.
     *
     * @param int $author
     */
    public function setAuthor($author);
}
