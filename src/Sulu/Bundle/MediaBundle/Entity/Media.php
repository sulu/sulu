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
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Media.
 */
class Media implements AuditableInterface
{
    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $files;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     * @Exclude
     */
    private $collection;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\MediaType
     */
    private $type;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $creator;

    /**
     * @var Media
     */
    private $previewImage;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->files = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return $this
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
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
     * Add files.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\File $files
     *
     * @return Media
     */
    public function addFile(\Sulu\Bundle\MediaBundle\Entity\File $files)
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * Remove files.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\File $files
     */
    public function removeFile(\Sulu\Bundle\MediaBundle\Entity\File $files)
    {
        $this->files->removeElement($files);
    }

    /**
     * Get files.
     *
     * @return File[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set collection.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $collection
     *
     * @return Media
     */
    public function setCollection(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collectionInterface.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Set type.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\MediaType $type
     *
     * @return Media
     */
    public function setType(\Sulu\Bundle\MediaBundle\Entity\MediaType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\MediaType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set changer.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $changer
     *
     * @return Media
     */
    public function setChanger(\Sulu\Component\Security\Authentication\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $creator
     *
     * @return Media
     */
    public function setCreator(\Sulu\Component\Security\Authentication\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set preview image.
     *
     * @param Media $previewImage
     *
     * @return Media
     */
    public function setPreviewImage(Media $previewImage = null)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * Get preview image.
     *
     * @return Media
     */
    public function getPreviewImage()
    {
        return $this->previewImage;
    }
}
