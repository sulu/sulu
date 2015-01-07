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
 * BaseCollection
 */
abstract class BaseCollection implements CollectionInterface
{
    /**
     * @var string
     */
    private $style;

    /**
     * @var integer
     * @Exclude
     */
    private $lft;

    /**
     * @var integer
     * @Exclude
     */
    private $rgt;

    /**
     * @var integer
     * @Exclude
     */
    private $depth;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    private $parent;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\CollectionType
     */
    private $type;

    /**
     * @var \Sulu\Component\Security\UserInterface
     * @Exclude
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\UserInterface
     * @Exclude
     */
    private $creator;

    /**
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return CollectionInterface
     */
    public function setChanger(\Sulu\Component\Security\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return CollectionInterface
     */
    public function setCreator(\Sulu\Component\Security\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->meta = new \Doctrine\Common\Collections\ArrayCollection();
        $this->media = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set style
     *
     * @param string $style
     * @return CollectionInterface
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return CollectionInterface
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return CollectionInterface
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth
     *
     * @param integer $depth
     * @return CollectionInterface
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return CollectionInterface
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return CollectionInterface
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set parent
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent
     * @return CollectionInterface
     */
    public function setParent(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set type
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionType $type
     * @return CollectionInterface
     */
    public function setType(\Sulu\Bundle\MediaBundle\Entity\CollectionType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionType
     */
    public function getType()
    {
        return $this->type;
    }
}
