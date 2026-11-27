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

class ArticleWorkflowTransitionAppliedEvent extends DomainEvent
{
    public function __construct(
        private ArticleInterface $article,
        private string $workflowTransitionName,
        private string $locale,
        private bool $reviewBypassed = false,
    ) {
        parent::__construct();
    }

    public function getArticle(): ArticleInterface
    {
        return $this->article;
    }

    public function getWorkflowTransitionName(): string
    {
        return $this->workflowTransitionName;
    }

    /**
     * Whether the transition published the content while its request for publishing still waited for approvals.
     */
    public function isReviewBypassed(): bool
    {
        return $this->reviewBypassed;
    }

    public function getEventType(): string
    {
        return 'workflow_transition.' . ($this->reviewBypassed ? 'publish_without_review' : $this->workflowTransitionName);
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
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        $dimensionContentCollection = new DimensionContentCollection($this->article->getDimensionContents(), [], ArticleDimensionContent::class);

        return $dimensionContentCollection->getDimensionContent(['locale' => $this->locale])?->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }
}
