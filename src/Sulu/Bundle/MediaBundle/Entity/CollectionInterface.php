<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * CollectionInterface.
 */
interface CollectionInterface extends AuditableInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set changer.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $changer
     *
     * @return CollectionInterface
     */
    public function setChanger(\Sulu\Component\Security\Authentication\UserInterface $changer = null);

    /**
     * Get changer.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getChanger();

    /**
     * Set creator.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $creator
     *
     * @return CollectionInterface
     */
    public function setCreator(\Sulu\Component\Security\Authentication\UserInterface $creator = null);

    /**
     * Get creator.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
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
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent
     *
     * @return CollectionInterface
     */
    public function setParent(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent = null);

    /**
     * Get parent.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    public function getParent();

    /**
     * Set type.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionType $type
     *
     * @return CollectionInterface
     */
    public function setType(\Sulu\Bundle\MediaBundle\Entity\CollectionType $type);

    /**
     * Get type.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionType
     */
    public function getType();

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $children
     */
    public function setChildren($children);
}
