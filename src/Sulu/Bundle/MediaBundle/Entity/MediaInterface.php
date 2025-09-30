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

/**
 * MediaInterface.
 */
interface MediaInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'media';

    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_DOCUMENT = 'document';

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

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

    public function setType(string $type): self;

    public function getType(): string;

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
