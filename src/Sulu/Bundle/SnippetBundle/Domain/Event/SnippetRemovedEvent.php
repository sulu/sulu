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

class SnippetRemovedEvent extends DomainEvent
{
    /**
     * @var string
     */
    private $snippetId;

    /**
     * @var string|null
     */
    private $snippetTitle;

    /**
     * @var string|null
     */
    private $snippetTitleLocale;

    public function __construct(string $snippetId, ?string $snippetTitle, ?string $snippetTitleLocale)
    {
        parent::__construct();

        $this->snippetId = $snippetId;
        $this->snippetTitle = $snippetTitle;
        $this->snippetTitleLocale = $snippetTitleLocale;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return SnippetDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->snippetId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->snippetTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->snippetTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::SECURITY_CONTEXT;
    }
}
