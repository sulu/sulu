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

namespace Sulu\Snippet\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;

class SnippetWorkflowTransitionAppliedEvent extends DomainEvent
{
    public function __construct(
        private SnippetInterface $snippet,
        private string $workflowTransitionName,
        private string $locale,
        private bool $reviewBypassed = false,
    ) {
        parent::__construct();
    }

    public function getSnippet(): SnippetInterface
    {
        return $this->snippet;
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
        return SnippetInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->snippet->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        $dimensionContentCollection = new DimensionContentCollection($this->snippet->getDimensionContents(), [], SnippetDimensionContent::class);

        return $dimensionContentCollection->getDimensionContent(['locale' => $this->locale])?->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::SECURITY_CONTEXT;
    }
}
