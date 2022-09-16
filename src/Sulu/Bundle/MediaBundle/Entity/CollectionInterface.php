<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
 * @method CollectionMeta getDefaultMeta();
 * @method DoctrineCollection|CollectionMeta[] getMeta();
 */
interface CollectionInterface extends AuditableInterface, SecuredEntityInterface
{
    public const RESOURCE_KEY = 'collections';

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Get key.
     *
     * @return string|null
     */
    public function getKey();

    /**
     * Set key.
     *
     * @param string|null $key
     *
     * @return CollectionInterface
     */
    public function setKey($key);

    /**
     * Set changer.
     *
     * @return CollectionInterface
     */
    public function setChanger(UserInterface $changer = null);

    /**
     * Get changer.
     *
     * @return UserInterface|null
     */
    public function getChanger();

    /**
     * Set creator.
     *
     * @return CollectionInterface
     */
    public function setCreator(UserInterface $creator = null);

    /**
     * Get creator.
     *
     * @return UserInterface|null
     */
    public function getCreator();

    /**
     * Set style.
     *
     * @param string|null $style
     *
     * @return CollectionInterface
     */
    public function setStyle($style);

    /**
     * Get style.
     *
     * @return string|null
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
     * @return CollectionInterface
     */
    public function setParent(self $parent = null);

    /**
     * Get parent.
     *
     * @return CollectionInterface|null
     */
    public function getParent();

    /**
     * Set type.
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
     * @param DoctrineCollection<int, CollectionInterface> $children
     *
     * @return void
     */
    public function setChildren(DoctrineCollection $children);
}
