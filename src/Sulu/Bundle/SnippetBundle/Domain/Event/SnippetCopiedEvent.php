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

class SnippetCopiedEvent extends DomainEvent
{
    /**
     * @var SnippetDocument
     */
    private $snippetDocument;

    /**
     * @var string
     */
    private $sourceSnippetId;

    /**
     * @var string|null
     */
    private $sourceSnippetTitle;

    /**
     * @var string|null
     */
    private $sourceSnippetTitleLocale;

    public function __construct(
        SnippetDocument $snippetDocument,
        string $sourceSnippetId,
        ?string $sourceSnippetTitle,
        ?string $sourceSnippetTitleLocale
    ) {
        parent::__construct();

        $this->snippetDocument = $snippetDocument;
        $this->sourceSnippetId = $sourceSnippetId;
        $this->sourceSnippetTitle = $sourceSnippetTitle;
        $this->sourceSnippetTitleLocale = $sourceSnippetTitleLocale;
    }

    /**
     * @deprecated
     */
    public function getPageDocument(): SnippetDocument
    {
        @trigger_deprecation('sulu/sulu', '2.4', 'The "%s" method is deprecated. Use "%s" instead.', __METHOD__, 'getSnippetDocument');

        return $this->getSnippetDocument();
    }

    public function getSnippetDocument(): SnippetDocument
    {
        return $this->snippetDocument;
    }

    public function getEventType(): string
    {
        return 'copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourceSnippetId' => $this->sourceSnippetId,
            'sourceSnippetTitle' => $this->sourceSnippetTitle,
            'sourceSnippetTitleLocale' => $this->sourceSnippetTitleLocale,
        ];
    }

    public function getResourceKey(): string
    {
        return SnippetDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->snippetDocument->getUuid();
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
