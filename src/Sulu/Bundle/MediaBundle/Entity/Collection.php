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

use Doctrine\ORM\Mapping as ORM;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Contact
 */
class Collection extends ApiEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var CollectionType
     */
    private $collectionType;

    /**
     * @var string
     */
    private $style;

    /**
     * @var integer
     */
    private $lft;

    /**
     * @var integer
     */
    private $rgt;

    /**
     * @var integer
     */
    private $depth;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $creator;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $medias;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->medias = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param \DateTime $changed
     * @return Collection
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return Collection
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;
        return $this;
    }

    /**
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * @param \DateTime $created
     * @return Collection
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return Collection
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $medias
     * @return Collection
     */
    public function setMedias($medias)
    {
        $this->medias = $medias;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @param int $id
     * @return Collection;
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return Collection
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $depth
     * @return Collection
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @param int $lft
     * @return Collection
     */
    public function setLft($lft)
    {
        $this->lft = $lft;
        return $this;
    }

    /**
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @param int $rgt
     * @return Collection
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
        return $this;
    }

    /**
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param string $style
     * @return Collection
     */
    public function setStyle($style)
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionType $collectionType
     * @return Collection
     */
    public function setCollectionType($collectionType)
    {
        $this->collectionType = $collectionType;
        return $this;
    }

    /**
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionType
     */
    public function getCollectionType()
    {
        return $this->collectionType;
    }
}