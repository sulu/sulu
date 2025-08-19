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
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;

class SnippetRemovedEvent extends DomainEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $snippetId,
        private ?string $snippetTitle,
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
        return SnippetInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->snippetId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->snippetTitle;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::SECURITY_CONTEXT;
    }
}
