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
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;

interface CollectionInterface extends AuditableInterface, SecuredEntityInterface
{
    public const RESOURCE_KEY = 'collections';

    public function getId(): int;

    public function isNew(): bool;

    public function getKey(): ?string;

    public function setKey(?string $key): static;

    public function setStyle(?string $style): static;

    public function getStyle(): ?string;

    public function setLft(int $lft): static;

    public function getLft(): int;

    public function setRgt(int $rgt): static;

    public function getRgt(): int;

    public function setDepth(int $depth): static;

    public function getDepth(): int;

    public function setParent(?self $parent = null): static;

    public function getParent(): ?self;

    public function setType(CollectionType $type): static;

    public function getType(): CollectionType;

    /**
     * @param DoctrineCollection<int, CollectionInterface> $children
     */
    public function setChildren(DoctrineCollection $children): static;

    /**
     * @return DoctrineCollection<int, self>
     */
    public function getChildren(): DoctrineCollection;

    public function addMeta(CollectionMeta $meta): static;

    public function removeMeta(CollectionMeta $meta): static;

    /**
     * @return DoctrineCollection<int, CollectionMeta>
     */
    public function getMeta(): DoctrineCollection;

    public function addMedia(MediaInterface $media): static;

    public function removeMedia(MediaInterface $media): static;

    /**
     * @return DoctrineCollection<int, MediaInterface>
     */
    public function getMedia(): DoctrineCollection;

    public function addChildren(self $children): static;

    public function removeChildren(self $children): static;

    public function setDefaultMeta(?CollectionMeta $defaultMeta = null): static;

    public function getDefaultMeta(): ?CollectionMeta;
}
