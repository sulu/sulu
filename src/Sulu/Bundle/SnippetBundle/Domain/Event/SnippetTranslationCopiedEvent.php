<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;

class SnippetTranslationCopiedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private SnippetDocument $snippetDocument,
        private string $locale,
        private string $sourceLocale,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getSnippetDocument(): SnippetDocument
    {
        return $this->snippetDocument;
    }

    public function getEventType(): string
    {
        return 'translation_copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourceLocale' => $this->sourceLocale,
        ];
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return SnippetDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->snippetDocument->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        return $this->snippetDocument->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->snippetDocument->getLocale();
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::SECURITY_CONTEXT;
    }
}
