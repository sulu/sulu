<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;

/**
 * CollectionInterface.
 */
interface CollectionInterface extends AuditableInterface, SecuredEntityInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey();

    /**
     * Set key.
     *
     * @param string $key
     */
    public function setKey($key);

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return CollectionInterface
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
     * @return CollectionInterface
     */
    public function setCreator(UserInterface $creator = null);

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator();

    /**
     * Set style.
     *
     * @param string $style
     *
     * @return CollectionInterface
     */
    public function setStyle($style);

    /**
     * Get style.
     *
     * @return string
     */
    public function getStyle();

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return CollectionInterface
     */
    public function setLft($lft);

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft();

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return CollectionInterface
     */
    public function setRgt($rgt);

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt();

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return CollectionInterface
     */
    public function setDepth($depth);

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth();

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
     * Set parent.
     *
     * @param CollectionInterface $parent
     *
     * @return CollectionInterface
     */
    public function setParent(CollectionInterface $parent = null);

    /**
     * Get parent.
     *
     * @return CollectionInterface
     */
    public function getParent();

    /**
     * Set type.
     *
     * @param CollectionType $type
     *
     * @return CollectionInterface
     */
    public function setType(CollectionType $type);

    /**
     * Get type.
     *
     * @return CollectionType
     */
    public function getType();

    /**
     * @param DoctrineCollection $children
     */
    public function setChildren(DoctrineCollection $children);
}
