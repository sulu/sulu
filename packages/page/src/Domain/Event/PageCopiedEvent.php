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
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;

class PageCopiedEvent extends DomainEvent
{
    public function __construct(
        private PageInterface $page,
        private string $sourcePageId,
        private string $sourcePageWebspaceKey,
        private ?string $sourcePageTitle,
        private ?string $sourcePageTitleLocale
    ) {
        parent::__construct();
    }

    public function getPage(): PageInterface
    {
        return $this->page;
    }

    public function getEventType(): string
    {
        return 'copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourcePageId' => $this->sourcePageId,
            'sourcePageWebspaceKey' => $this->sourcePageWebspaceKey,
            'sourcePageTitle' => $this->sourcePageTitle,
            'sourcePageTitleLocale' => $this->sourcePageTitleLocale,
        ];
    }

    public function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->page->getUuid();
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->page->getWebspaceKey();
    }

    public function getResourceTitle(): ?string
    {
        return $this->sourcePageTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->sourcePageTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::getPageSecurityContext(static::getResourceWebspaceKey());
    }
}
