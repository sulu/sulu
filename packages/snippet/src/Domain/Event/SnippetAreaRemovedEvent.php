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
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAreaAdmin;

class SnippetAreaRemovedEvent extends DomainEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $snippetAreaId,
        private ?string $snippetAreaTitle,
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
        return SnippetAreaInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->snippetAreaId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->snippetAreaTitle;
    }

    public function getAreaKey(): ?string
    {
        // SnippetArea has no translatable title, so area key serves as the resource title
        return $this->snippetAreaTitle;
    }

    public function getResourceSecurityContext(): ?string
    {
        $webspaceKey = $this->context['webspaceKey'] ?? null;
        if (null === $webspaceKey || !\is_string($webspaceKey)) {
            return null;
        }

        return SnippetAreaAdmin::getSecurityContext($webspaceKey);
    }
}
