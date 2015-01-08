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

use JMS\Serializer\Annotation\Exclude;

/**
 * CollectionInterface
 */
interface CollectionInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return CollectionInterface
     */
    public function setChanger(\Sulu\Component\Security\UserInterface $changer = null);

    /**
     * Get changer
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger();

    /**
     * Set creator
     *
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return CollectionInterface
     */
    public function setCreator(\Sulu\Component\Security\UserInterface $creator = null);

    /**
     * Get creator
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator();

    /**
     * Set style
     *
     * @param string $style
     * @return CollectionInterface
     */
    public function setStyle($style);

    /**
     * Get style
     *
     * @return string
     */
    public function getStyle();

    /**
     * Set lft
     *
     * @param integer $lft
     * @return CollectionInterface
     */
    public function setLft($lft);

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft();

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return CollectionInterface
     */
    public function setRgt($rgt);

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt();

    /**
     * Set depth
     *
     * @param integer $depth
     * @return CollectionInterface
     */
    public function setDepth($depth);

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth();

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return CollectionInterface
     */
    public function setCreated($created);

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return CollectionInterface
     */
    public function setChanged($changed);

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Set parent
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent
     * @return CollectionInterface
     */
    public function setParent(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent = null);

    /**
     * Get parent
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    public function getParent();

    /**
     * Set type
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionType $type
     * @return CollectionInterface
     */
    public function setType(\Sulu\Bundle\MediaBundle\Entity\CollectionType $type);

    /**
     * Get type
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionType
     */
    public function getType();
}
