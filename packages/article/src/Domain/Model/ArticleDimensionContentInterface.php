<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Domain\Model;

use Sulu\Content\Domain\Model\AuditableInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;

/**
 * @extends DimensionContentInterface<ArticleInterface>
 */
interface ArticleDimensionContentInterface extends DimensionContentInterface, ExcerptInterface, TaxonomyInterface, SeoInterface, TemplateInterface, RoutableInterface, WorkflowInterface, ShadowInterface, WebspaceInterface, AuthorInterface, AuditableInterface
{
    public function getTitle(): ?string;

    public function getCustomizeWebspaceSettings(): bool;

    public function setCustomizeWebspaceSettings(bool $customizeWebspaceSettings): void;

    /**
     * @return string[]
     */
    public function getAdditionalWebspaces(): array;

    /**
     * @param string[] $additionalWebspaces
     */
    public function setAdditionalWebspaces(array $additionalWebspaces): self;

    public function addAdditionalWebspace(string $additionalWebspace): self;

    public function hasAdditionalWebspace(string $additionalWebspace): bool;
}
