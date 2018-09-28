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

use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * MediaInterface.
 */
interface MediaInterface extends AuditableInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return $this
     */
    public function setChanged($changed);

    /**
     * Add files.
     *
     * @param File $files
     *
     * @return Media
     */
    public function addFile(File $files);

    /**
     * Remove files.
     *
     * @param File $files
     */
    public function removeFile(File $files);

    /**
     * Get files.
     *
     * @return File[]
     */
    public function getFiles();

    /**
     * Set collection.
     *
     * @param CollectionInterface $collection
     *
     * @return Media
     */
    public function setCollection(CollectionInterface $collection);

    /**
     * Get collectionInterface.
     *
     * @return CollectionInterface
     */
    public function getCollection();

    /**
     * Set type.
     *
     * @param MediaType $type
     *
     * @return Media
     */
    public function setType(MediaType $type);

    /**
     * Get type.
     *
     * @return MediaType
     */
    public function getType();

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return Media
     */
    public function setChanger($changer);

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return Media
     */
    public function setCreator($creator);

    /**
     * Set preview image.
     *
     * @param Media $previewImage
     *
     * @return Media
     */
    public function setPreviewImage(Media $previewImage = null);

    /**
     * Get preview image.
     *
     * @return Media
     */
    public function getPreviewImage();
}
