<?php

declare(strict_types=1);

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
use Sulu\Content\Domain\Model\AuditableTrait;
use Sulu\Content\Domain\Model\AuthorTrait;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Domain\Model\ExcerptTrait;
use Sulu\Content\Domain\Model\LinkTrait;
use Sulu\Content\Domain\Model\RoutableTrait;
use Sulu\Content\Domain\Model\SeoTrait;
use Sulu\Content\Domain\Model\ShadowTrait;
use Sulu\Content\Domain\Model\TaxonomyTrait;
use Sulu\Content\Domain\Model\TemplateTrait;
use Sulu\Content\Domain\Model\WorkflowTrait;
use Sulu\Route\Domain\Model\Route;

class PageDimensionContent implements PageDimensionContentInterface
{
    use AuditableTrait;
    use AuthorTrait;
    use DimensionContentTrait;
    use ExcerptTrait;
    use TaxonomyTrait;
    use RoutableTrait {
        setRoute as parentSetRoute;
    }
    use SeoTrait;
    use ShadowTrait;
    use TemplateTrait {
        TemplateTrait::setTemplateData as parentSetTemplateData;
    }
    use LinkTrait;
    use WorkflowTrait;

    protected int $id;

    protected PageInterface $page;

    protected ?string $title = null;

    /**
     * @var Collection<int, PageDimensionContentNavigationContext>
     */
    protected Collection $navigationContexts;

    public function __construct(PageInterface $page)
    {
        $this->page = $page;
        $this->navigationContexts = new ArrayCollection();
        $this->created = new \DateTimeImmutable();
        $this->changed = new \DateTimeImmutable();
    }

    /**
     * @return PageInterface
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->page;
    }

    /**
     * @internal the returned clone is detached from Doctrine's unit of work and must not be persisted
     */
    public function withPage(PageInterface $page): static
    {
        $clone = clone $this;
        $clone->page = $page;

        return $clone;
    }

    /**
     * @internal the returned clone is detached from Doctrine's unit of work and must not be persisted
     */
    public function withRoute(?Route $route): static
    {
        $clone = clone $this;
        $clone->route = $route;

        return $clone;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTemplateData(array $templateData): void
    {
        if (\array_key_exists('title', $templateData)
            && \is_string($templateData['title'])
        ) {
            $this->title = $templateData['title'];
        }

        $this->parentSetTemplateData($templateData);
    }

    public function setRoute(Route $route): void
    {
        $route->setWebspace($this->getResource()->getWebspaceKey());

        $this->parentSetRoute($route);
    }

    public static function getTemplateType(): string
    {
        return PageInterface::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function getNavigationContexts(): array
    {
        return \array_values(\array_map(
            fn ($context) => $context->getNavigationContext(),
            $this->navigationContexts->toArray(),
        ));
    }

    public function setNavigationContexts(array $navigationContexts): static
    {
        $existingContexts = [];
        foreach ($this->navigationContexts as $existingPageNavigationContext) {
            $existingContexts[$existingPageNavigationContext->getNavigationContext()] = $existingPageNavigationContext;
        }

        foreach ($navigationContexts as $navigationContext) {
            if (!\array_key_exists($navigationContext, $existingContexts)) {
                $this->navigationContexts->add($this->createNavigationContext($navigationContext));
            }
            unset($existingContexts[$navigationContext]);
        }

        foreach ($existingContexts as $navigationContext) {
            $this->navigationContexts->removeElement($navigationContext);
        }

        return $this;
    }

    public function addNavigationContext(string $navigationContext): static
    {
        if (!$this->hasNavigationContext($navigationContext)) {
            $this->navigationContexts->add($this->createNavigationContext($navigationContext));
        }

        return $this;
    }

    private function createNavigationContext(string $navigationContext): PageDimensionContentNavigationContext
    {
        return new PageDimensionContentNavigationContext(
            $navigationContext,
            $this,
        );
    }

    public function removeNavigationContext(string $navigationContext): static
    {
        foreach ($this->navigationContexts as $pageDimensionNavigationContext) {
            if ($pageDimensionNavigationContext->getNavigationContext() === $navigationContext) {
                $this->navigationContexts->removeElement($pageDimensionNavigationContext);

                return $this;
            }
        }

        return $this;
    }

    public function hasNavigationContext(string $navigationContext): bool
    {
        foreach ($this->navigationContexts as $pageDimensionNavigationContext) {
            if ($pageDimensionNavigationContext->getNavigationContext() === $navigationContext) {
                return true;
            }
        }

        return false;
    }
}
