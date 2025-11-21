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

namespace Sulu\Article\Domain\Model;

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
use Sulu\Content\Domain\Model\TaxonomyTrait;
use Sulu\Content\Domain\Model\TemplateTrait;
use Sulu\Content\Domain\Model\WebspaceTrait;
use Sulu\Content\Domain\Model\WorkflowTrait;

/**
 * @experimental
 */
class ArticleDimensionContent implements ArticleDimensionContentInterface
{
    use AuditableTrait;
    use AuthorTrait;
    use DimensionContentTrait;
    use ExcerptTrait;
    use TaxonomyTrait;
    use RoutableTrait;
    use SeoTrait;
    use ShadowTrait;
    use TemplateTrait {
        TemplateTrait::setTemplateData as parentSetTemplateData;
    }
    use WebspaceTrait;
    use WorkflowTrait;

    protected int $id;

    protected ArticleInterface $article;

    protected ?string $title = null;

    private bool $customizeWebspaceSettings = false;

    /**
     * @var Collection<int, ArticleDimensionContentAdditionalWebspace>
     */
    protected Collection $additionalWebspaces;

    public function __construct(ArticleInterface $article)
    {
        $this->article = $article;
        $this->additionalWebspaces = new ArrayCollection();
        $this->created = new \DateTimeImmutable();
        $this->changed = new \DateTimeImmutable();
    }

    /**
     * @return ArticleInterface
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->article;
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

    public static function getTemplateType(): string
    {
        return ArticleInterface::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }

    public function getCustomizeWebspaceSettings(): bool
    {
        return $this->customizeWebspaceSettings;
    }

    public function setCustomizeWebspaceSettings(bool $customizeWebspaceSettings): void
    {
        $this->customizeWebspaceSettings = $customizeWebspaceSettings;
    }

    /**
     * @return string[]
     */
    public function getAdditionalWebspaces(): array
    {
        return \array_values(\array_map(
            fn ($webspace) => $webspace->getAdditionalWebspace(),
            $this->additionalWebspaces->toArray(),
        ));
    }

    /**
     * @param string[] $additionalWebspaces
     */
    public function setAdditionalWebspaces(array $additionalWebspaces): self
    {
        $existingAdditionalWebspaces = [];
        foreach ($this->additionalWebspaces as $existingAdditionalWebspace) {
            $existingAdditionalWebspaces[$existingAdditionalWebspace->getAdditionalWebspace()] = $existingAdditionalWebspace;
        }

        foreach ($additionalWebspaces as $additionalWebspace) {
            if (!\array_key_exists($additionalWebspace, $existingAdditionalWebspaces)) {
                $this->additionalWebspaces->add($this->createAdditionalWebspace($additionalWebspace));
            }
            unset($existingAdditionalWebspaces[$additionalWebspace]);
        }

        foreach ($existingAdditionalWebspaces as $additionalWebspace) {
            $this->additionalWebspaces->removeElement($additionalWebspace);
        }

        return $this;
    }

    public function addAdditionalWebspace(string $additionalWebspace): self
    {
        if (!$this->hasAdditionalWebspace($additionalWebspace)) {
            $this->additionalWebspaces->add($this->createAdditionalWebspace($additionalWebspace));
        }

        return $this;
    }

    public function hasAdditionalWebspace(string $additionalWebspace): bool
    {
        foreach ($this->additionalWebspaces as $webspace) {
            if ($webspace->getAdditionalWebspace() === $additionalWebspace) {
                return true;
            }
        }

        return false;
    }

    private function createAdditionalWebspace(string $additionalWebspace): ArticleDimensionContentAdditionalWebspace
    {
        return new ArticleDimensionContentAdditionalWebspace(
            $additionalWebspace,
            $this,
        );
    }
}
