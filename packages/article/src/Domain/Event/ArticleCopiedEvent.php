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

namespace Sulu\Article\Domain\Event;

use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Content\Domain\Model\DimensionContentCollection;

class ArticleCopiedEvent extends DomainEvent
{
    public function __construct(
        private ArticleInterface $article,
        private string $sourceArticleId,
        private ?string $sourceArticleTitle,
        private ?string $sourceArticleTitleLocale,
    ) {
        parent::__construct();
    }

    public function getArticle(): ArticleInterface
    {
        return $this->article;
    }

    public function getEventType(): string
    {
        return 'copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourceArticleId' => $this->sourceArticleId,
            'sourceArticleTitle' => $this->sourceArticleTitle,
            'sourceArticleTitleLocale' => $this->sourceArticleTitleLocale,
        ];
    }

    public function getResourceKey(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->article->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return $this->sourceArticleTitleLocale;
    }

    public function getResourceTitle(): ?string
    {
        if (null === $this->sourceArticleTitleLocale) {
            return null;
        }

        $dimensionContentCollection = new DimensionContentCollection($this->article->getDimensionContents(), [], ArticleDimensionContent::class);

        return $dimensionContentCollection->getDimensionContent(['locale' => $this->sourceArticleTitleLocale])?->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->sourceArticleTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }
}
