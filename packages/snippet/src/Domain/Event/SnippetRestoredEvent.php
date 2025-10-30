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

class SnippetRestoredEvent extends DomainEvent
{
    /**
     * @param array{
     *     locales?: string[]
     * } $context
     * @param mixed[] $payload
     */
    public function __construct(
        private SnippetInterface $snippet,
        private ?string $snippetTitle,
        private array $context,
        private array $payload,
    ) {
        parent::__construct();
    }

    public function getSnippet(): SnippetInterface
    {
        return $this->snippet;
    }

    public function getEventType(): string
    {
        return 'restored';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return SnippetInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->snippet->getUuid();
    }

    public function getResourceTitle(): ?string
    {
        return $this->snippetTitle;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::SECURITY_CONTEXT;
    }

    /**
     * @return string[]|null
     */
    public function getAllLocales(): ?array
    {
        return $this->context['locales'] ?? null;
    }
}
