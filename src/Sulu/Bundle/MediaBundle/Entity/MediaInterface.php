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
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Set changed.
     *
     * @return $this
     */
    public function setChanged(\DateTime $changed);

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Add files.
     *
     * @return Media
     */
    public function addFile(File $files);

    /**
     * Remove files.
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
     * @return Media
     */
    public function setCreator(UserInterface $creator = null);

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator();

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
