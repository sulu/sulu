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

namespace Sulu\Bundle\PageBundle\Domain\Event;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Domain\PageInterface;

class PageCopiedEvent extends DomainEvent
{
    /**
     * @var PageDocument
     */
    private $pageDocument;

    /**
     * @var string
     */
    private $copiedPageId;

    /**
     * @var string
     */
    private $copiedPageWebspaceKey;

    /**
     * @var string|null
     */
    private $copiedPageTitle;

    /**
     * @var string|null
     */
    private $copiedPageTitleLocale;

    public function __construct(
        PageDocument $pageDocument,
        string $copiedPageId,
        string $copiedPageWebspaceKey,
        ?string $copiedPageTitle,
        ?string $copiedPageTitleLocale
    ) {
        parent::__construct();

        $this->pageDocument = $pageDocument;
        $this->copiedPageId = $copiedPageId;
        $this->copiedPageWebspaceKey = $copiedPageWebspaceKey;
        $this->copiedPageTitle = $copiedPageTitle;
        $this->copiedPageTitleLocale = $copiedPageTitleLocale;
    }

    public function getPageDocument(): PageDocument
    {
        return $this->pageDocument;
    }

    public function getEventType(): string
    {
        return 'copied';
    }

    public function getEventContext(): array
    {
        return [
            'copiedPageId' => $this->copiedPageId,
            'copiedPageWebspaceKey' => $this->copiedPageWebspaceKey,
            'copiedPageTitle' => $this->copiedPageTitle,
            'copiedPageTitleLocale' => $this->copiedPageTitleLocale,
        ];
    }

    public function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->pageDocument->getUuid();
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->pageDocument->getWebspaceName();
    }

    public function getResourceTitle(): ?string
    {
        return $this->pageDocument->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->pageDocument->getLocale();
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::getPageSecurityContext(static::getResourceWebspaceKey());
    }
}
