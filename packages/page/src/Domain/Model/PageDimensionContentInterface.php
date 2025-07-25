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

use Sulu\Content\Domain\Model\AuditableInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;

/**
 * @experimental
 *
 * @extends DimensionContentInterface<PageInterface>
 */
interface PageDimensionContentInterface extends DimensionContentInterface, ExcerptInterface, SeoInterface, TemplateInterface, RoutableInterface, WorkflowInterface, ShadowInterface, AuthorInterface, AuditableInterface
{
    public function getTitle(): ?string;

    /**
     * @return string[]
     */
    public function getNavigationContexts(): array;

    /**
     * @param string[] $navigationContexts
     */
    public function setNavigationContexts(array $navigationContexts): self;

    public function addNavigationContext(string $navigationContext): self;

    public function removeNavigationContext(string $navigationContext): self;

    public function hasNavigationContext(string $navigationContext): bool;
}
