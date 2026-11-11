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

    public function getId(): int;

    public function isNew(): bool;

    public function addFile(File $files): static;

    public function removeFile(File $files): static;

    /**
     * @return DoctrineCollection<int, File>
     */
    public function getFiles(): DoctrineCollection;

    public function setCollection(CollectionInterface $collection): static;

    public function getCollection(): CollectionInterface;

    public function setType(string $type): self;

    public function getType(): string;

    public function setPreviewImage(?self $previewImage = null): static;

    public function getPreviewImage(): ?self;
}
