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
use Sulu\Bundle\SecurityBundle\Entity\PermissionInheritanceInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Collection.
 */
class Collection implements CollectionInterface, PermissionInheritanceInterface
{
    use AuditableTrait;

    protected int $id;

    protected ?string $style = null;

    #[Exclude]
    protected int $lft;

    #[Exclude]
    protected int $rgt;

    #[Exclude]
    protected int $depth;

    protected CollectionType $type;

    #[Exclude]
    protected ?UserInterface $changer = null;

    #[Exclude]
    protected ?UserInterface $creator = null;

    private ?string $key = null;

    /** @var DoctrineCollection<int, CollectionMeta> */
    private DoctrineCollection $meta;

    /** @var DoctrineCollection<int, MediaInterface> */
    #[Exclude]
    private DoctrineCollection $media;

    /** @var DoctrineCollection<int, CollectionInterface> */
    private DoctrineCollection $children;

    private ?CollectionInterface $parent = null;

    private ?CollectionMeta $defaultMeta = null;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function setStyle(?string $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function getStyle(): ?string
    {
        return $this->style;
    }

    public function setLft(int $lft): static
    {
        $this->lft = $lft;

        return $this;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function setRgt(int $rgt): static
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRgt(): int
    {
        return $this->rgt;
    }

    public function setDepth(int $depth): static
    {
        $this->depth = $depth;

        return $this;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setType(CollectionType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): CollectionType
    {
        return $this->type;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return DoctrineCollection<int, CollectionInterface>
     */
    public function getChildren(): DoctrineCollection
    {
        return $this->children;
    }

    public function setChildren(DoctrineCollection $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function setParent(?CollectionInterface $parent = null): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?CollectionInterface
    {
        return $this->parent;
    }

    public function getParentId(): ?int
    {
        if ($this->parent) {
            return $this->parent->getId();
        }

        return null;
    }

    public function addMeta(CollectionMeta $meta): static
    {
        $this->meta[] = $meta;

        return $this;
    }

    public function removeMeta(CollectionMeta $meta): static
    {
        $this->meta->removeElement($meta);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, CollectionMeta>
     */
    public function getMeta(): DoctrineCollection
    {
        return $this->meta;
    }

    public function addMedia(MediaInterface $media): static
    {
        $this->media[] = $media;

        return $this;
    }

    public function removeMedia(MediaInterface $media): static
    {
        $this->media->removeElement($media);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, MediaInterface>
     */
    public function getMedia(): DoctrineCollection
    {
        return $this->media;
    }

    public function addChildren(CollectionInterface $children): static
    {
        $this->children[] = $children;

        return $this;
    }

    public function removeChildren(CollectionInterface $children): static
    {
        $this->children->removeElement($children);

        return $this;
    }

    public function setDefaultMeta(?CollectionMeta $defaultMeta = null): static
    {
        $this->defaultMeta = $defaultMeta;

        return $this;
    }

    public function getDefaultMeta(): ?CollectionMeta
    {
        return $this->defaultMeta;
    }

    public function getSecurityContext(): string
    {
        return 'sulu.media.collections';
    }
}
