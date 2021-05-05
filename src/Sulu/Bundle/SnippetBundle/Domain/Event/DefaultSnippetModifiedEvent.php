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

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;

class DefaultSnippetModifiedEvent extends DomainEvent
{
    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string
     */
    private $snippetAreaKey;

    /**
     * @var SnippetDocument
     */
    private $snippet;

    public function __construct(
        string $webspaceKey,
        string $snippetAreaKey,
        SnippetDocument $snippet
    ) {
        parent::__construct();

        $this->webspaceKey = $webspaceKey;
        $this->snippetAreaKey = $snippetAreaKey;
        $this->snippet = $snippet;
    }

    public function getSnippetAreaKey(): string
    {
        return $this->snippetAreaKey;
    }

    public function getSnippet(): SnippetDocument
    {
        return $this->snippet;
    }

    public function getEventType(): string
    {
        return 'default_snippet_modified';
    }

    public function getEventContext(): array
    {
        return [
            'snippetAreaKey' => $this->snippetAreaKey,
            'snippetId' => $this->snippet->getUuid(),
            'snippetTitle' => $this->snippet->getTitle(),
            'snippetTitleLocale' => $this->snippet->getLocale(),
        ];
    }

    public function getResourceKey(): string
    {
        return 'webspaces';
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->webspaceKey;
    }

    public function getResourceId(): string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->webspaceKey;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::getDefaultSnippetsSecurityContext($this->webspaceKey);
    }
}
