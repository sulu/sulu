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

use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * MediaInterface.
 */
interface MediaInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'media';

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return $this
     */
    public function setCreated($created);

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
     * @return MediaInterface
     */
    public function addFile(File $files);

    /**
     * Remove files.
     *
     * @return void
     */
    public function removeFile(File $files);

    /**
     * Get files.
     *
     * @return DoctrineCollection<int, File>
     */
    public function getFiles();

    /**
     * Set collection.
     *
     * @return MediaInterface
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
     * @return MediaInterface
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
     * @param UserInterface|null $changer
     *
     * @return MediaInterface
     */
    public function setChanger($changer);

    /**
     * Set creator.
     *
     * @param UserInterface|null $creator
     *
     * @return MediaInterface
     */
    public function setCreator($creator);

    /**
     * Set preview image.
     *
     * @return MediaInterface|null
     */
    public function setPreviewImage(?self $previewImage = null);

    /**
     * Get preview image.
     *
     * @return MediaInterface|null
     */
    public function getPreviewImage();
}
