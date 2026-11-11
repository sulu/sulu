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

    protected int $id;

    /** @var DoctrineCollection<int, File> */
    protected DoctrineCollection $files;

    #[Exclude]
    protected CollectionInterface $collection;

    protected string $type;

    protected ?MediaInterface $previewImage = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function addFile(File $files): static
    {
        $this->files[] = $files;

        return $this;
    }

    public function removeFile(File $files): static
    {
        $this->files->removeElement($files);

        return $this;
    }

    public function getFiles(): DoctrineCollection
    {
        return $this->files;
    }

    public function setCollection(CollectionInterface $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setPreviewImage(?MediaInterface $previewImage = null): static
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    public function getPreviewImage(): ?MediaInterface
    {
        return $this->previewImage;
    }
}
