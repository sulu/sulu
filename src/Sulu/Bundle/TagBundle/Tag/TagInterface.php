<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Interface for tag.
 */
interface TagInterface
{
    /**
     * Set name.
     *
     * @param string $name
     *
     * @return TagInterface
     */
    public function setName($name);

    /**
     * Get name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return TagInterface
     */
    public function setId($id);

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return TagInterface
     */
    public function setChanger(UserInterface $changer = null);

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger();

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return TagInterface
     */
    public function setCreator(UserInterface $creator = null);

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator();
}
