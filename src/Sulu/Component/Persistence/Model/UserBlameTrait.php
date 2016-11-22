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

use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Trait with basic implementation of UserBlameInterface.
 */
trait UserBlameTrait
{
    /**
     * @var UserInterface
     */
    protected $creator;

    /**
     * @var UserInterface
     */
    protected $changer;

    /**
     * @see UserBlameInterface::getCreator()
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @see UserBlameInterface::getChanger()
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return $this
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;

        return $this;
    }
}
