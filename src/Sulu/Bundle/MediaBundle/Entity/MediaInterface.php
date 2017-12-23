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
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return self
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
     * @param File $files
     *
     * @return self
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
     * Get file.
     *
     * @param string $locale
     * @param string $defaultLocale
     *
     * @return File
     */
    public function getFile($locale, $defaultLocale = null);

    /**
     * Set collection.
     *
     * @param CollectionInterface $collection
     *
     * @return self
     */
    public function setCollection(CollectionInterface $collection);

    /**
     * Get collection.
     *
     * @return CollectionInterface
     */
    public function getCollection();

    /**
     * Set type.
     *
     * @param MediaType $type
     *
     * @return self
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
     * @return self
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
     * @return self
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
     * @param self $previewImage
     *
     * @return self
     */
    public function setPreviewImage(self $previewImage = null);

    /**
     * Get preview image.
     *
     * @return MediaInterface
     */
    public function getPreviewImage();
}
