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

class SnippetAreaModifiedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private SnippetAreaInterface $snippetArea,
        private string $locale,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getSnippetArea(): SnippetAreaInterface
    {
        return $this->snippetArea;
    }

    public function getEventType(): string
    {
        return 'modified';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return SnippetAreaInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->snippetArea->getAreaKey();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceTitle(): string
    {
        return $this->snippetArea->getAreaKey();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAreaAdmin::SECURITY_CONTEXT;
    }
}
