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
 * Adds the user which is defined as author and an authored date.
 */
interface LocalizedAuthorBehavior extends LocalizedBlameBehavior
{
    /**
     * Returns lastModifiedEnabled.
     *
     * @return bool
     */
    public function getLastModifiedEnabled();

    /**
     * Set lastModifiedEnabled.
     *
     * @param bool $isLastModified
     *
     * @return void
     */
    public function setLastModifiedEnabled($isLastModified);

    /**
     * Returns lastModified-date.
     *
     * @return \DateTime
     */
    public function getLastModified();

    /**
     * Set lastModified-date.
     *
     * @param \DateTime|null $lastModified
     *
     * @return void
     */
    public function setLastModified($lastModified);

    /**
     * Returns author-date.
     *
     * @return \DateTime
     */
    public function getAuthored();

    /**
     * Set author-date.
     *
     * @param \DateTime $authored
     */
    public function setAuthored($authored);

    /**
     * Returns id of author contact.
     *
     * @return int|null
     */
    public function getAuthor();

    /**
     * Set id of author contact.
     *
     * @param int|null $contactId
     */
    public function setAuthor($contactId);
}
