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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * Media.
 */
class Media implements MediaInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var DoctrineCollection
     */
    protected $files;

    /**
     * @var CollectionInterface
     * @Exclude
     */
    protected $collection;

    /**
     * @var MediaType
     */
    protected $type;

    /**
     * @var Media
     */
    protected $previewImage;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
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
     * @param File $files
     *
     * @return Media
     */
    public function addFile(File $files)
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * Remove files.
     *
     * @param File $files
     */
    public function removeFile(File $files)
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
     * @param CollectionInterface $collection
     *
     * @return Media
     */
    public function setCollection(CollectionInterface $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collectionInterface.
     *
     * @return CollectionInterface
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Set type.
     *
     * @param MediaType $type
     *
     * @return Media
     */
    public function setType(MediaType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return MediaType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set preview image.
     *
     * @param Media $previewImage
     *
     * @return Media
     */
    public function setPreviewImage(self $previewImage = null)
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
