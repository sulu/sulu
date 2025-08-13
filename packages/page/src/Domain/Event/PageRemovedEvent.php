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

namespace Sulu\Page\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Page\Domain\Model\Page;

class PageRemovedEvent extends DomainEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $pageId,
        private string $webspaceKey,
        private ?string $pageTitle,
        private array $context = [],
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getEventContext(): array
    {
        return $this->context;
    }

    public function getResourceKey(): string
    {
        return Page::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->pageId;
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::getPageSecurityContext(static::getResourceWebspaceKey());
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return SecurityBehavior::class;
    }
}
