<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\UserContext;

/**
 * This class stores the current user context. Also allows updating it, in which case it is marked as changed.
 */
class UserContextStore implements UserContextStoreInterface
{
    /**
     * @var string
     */
    private $userContext = 0;

    /**
     * @var bool
     */
    private $changed = false;

    /**
     * {@inheritdoc}
     */
    public function setUserContext($userContext)
    {
        $this->userContext = $userContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContext()
    {
        return $this->userContext;
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserContext($userContext)
    {
        $this->setUserContext($userContext);
        $this->changed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasChanged()
    {
        return $this->changed;
    }
}
