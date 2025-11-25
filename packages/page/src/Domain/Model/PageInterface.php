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

use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;

/**
 * @extends ContentRichEntityInterface<PageDimensionContentInterface>
 */
interface PageInterface extends AuditableInterface, ContentRichEntityInterface, SecuredEntityInterface
{
    public const TEMPLATE_TYPE = 'page';
    public const RESOURCE_KEY = 'pages';

    /**
     * @internal
     */
    public function getId(): string;

    public function getUuid(): string;

    public function getWebspaceKey(): string;

    public function setWebspaceKey(string $webspaceKey): static;

    public function getLft(): int;

    public function setLft(int $lft): static;

    public function getRgt(): int;

    public function setRgt(int $rgt): static;

    public function getDepth(): int;

    public function setDepth(int $depth): static;

    public function getParent(): ?self;

    public function setParent(self $parent): static;

    /**
     * @return Collection<int, PageInterface>
     */
    public function getChildren(): Collection;

    public function addChild(self $child): static;

    public function removeChild(self $child): static;
}
