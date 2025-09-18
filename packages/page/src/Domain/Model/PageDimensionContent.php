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
use Sulu\Content\Domain\Model\RoutableTrait;
use Sulu\Content\Domain\Model\SeoTrait;
use Sulu\Content\Domain\Model\ShadowTrait;
use Sulu\Content\Domain\Model\TemplateTrait;
use Sulu\Content\Domain\Model\WorkflowTrait;
use Sulu\Route\Domain\Model\Route;

/**
 * @experimental
 */
class PageDimensionContent implements PageDimensionContentInterface
{
    use AuthorTrait;
    use DimensionContentTrait;
    use ExcerptTrait;
    use RoutableTrait {
        setRoute as parentSetRoute;
    }
    use SeoTrait;
    use ShadowTrait;
    use TemplateTrait {
        TemplateTrait::setTemplateData as parentSetTemplateData;
    }
    use WorkflowTrait;
    use AuditableTrait;

    protected int $id;

    protected PageInterface $page;

    protected ?string $title;

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
        $route->setSite($this->getResource()->getWebspaceKey());

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
        return \array_map(
            fn ($context) => $context->getNavigationContext(),
            $this->navigationContexts->toArray()
        );
    }

    public function setNavigationContexts(array $navigationContexts): self
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

    public function addNavigationContext(string $navigationContext): self
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
            $this
        );
    }

    public function removeNavigationContext(string $navigationContext): self
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
