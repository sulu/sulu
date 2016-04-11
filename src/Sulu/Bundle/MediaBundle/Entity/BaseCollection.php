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

use JMS\Serializer\Annotation\Exclude;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * BaseCollection.
 */
abstract class BaseCollection implements CollectionInterface
{
    /**
     * @var string
     */
    protected $style;

    /**
     * @var int
     * @Exclude
     */
    protected $lft;

    /**
     * @var int
     * @Exclude
     */
    protected $rgt;

    /**
     * @var int
     * @Exclude
     */
    protected $depth;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var CollectionType
     */
    protected $type;

    /**
     * @var UserInterface
     * @Exclude
     */
    protected $changer;

    /**
     * @var UserInterface
     * @Exclude
     */
    protected $creator;

    /**
     * @var string
     */
    private $key;

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return CollectionInterface
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return CollectionInterface
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set style.
     *
     * @param string $style
     *
     * @return CollectionInterface
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style.
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return CollectionInterface
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return CollectionInterface
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return CollectionInterface
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type.
     *
     * @param CollectionType $type
     *
     * @return CollectionInterface
     */
    public function setType(CollectionType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return CollectionType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get key.
     *
     * @param string $key
     *
     * @return CollectionInterface
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }
}
