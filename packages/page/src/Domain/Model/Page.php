<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
class Page implements PageInterface
{
    /**
     * @phpstan-use ContentRichEntityTrait<PageDimensionContentInterface>
     */
    use ContentRichEntityTrait;
    use AuditableTrait;

    protected string $uuid;

    private string $webspaceKey;

    private int $lft;

    private int $rgt;

    private int $depth;

    private ?PageInterface $parent = null;

    /**
     * @var Collection<int, PageInterface>
     */
    private Collection $children;

    public function __construct(
        ?string $uuid = null
    ) {
        $this->uuid = $uuid ?: Uuid::v7()->toRfc4122();
        $this->initializeDimensionContents();
        $this->children = new ArrayCollection();
    }

    public function getId(): string // TODO should be replaced by uuid
    {
        return $this->uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return PageDimensionContentInterface
     */
    public function createDimensionContent(): DimensionContentInterface
    {
        return new PageDimensionContent($this);
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function setWebspaceKey(string $webspaceKey): static
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    public function setParent(PageInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?PageInterface
    {
        return $this->parent;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(PageInterface $child): static
    {
        $this->children->add($child);

        return $this;
    }

    public function removeChild(PageInterface $child): static
    {
        $this->children->removeElement($child);

        return $this;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function setLft(int $lft): static
    {
        $this->lft = $lft;

        return $this;
    }

    public function getRgt(): int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): static
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): static
    {
        $this->depth = $depth;

        return $this;
    }
}
