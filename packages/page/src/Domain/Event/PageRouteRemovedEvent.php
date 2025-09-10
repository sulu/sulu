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

class PageRouteRemovedEvent extends DomainEvent
{
    public function __construct(
        private string $pageId,
        private string $webspaceKey,
        private string $pageTitle,
        private string $pageTitleLocale,
        private string $route
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'route_removed';
    }

    public function getEventContext(): array
    {
        return [
            'route' => $this->route,
        ];
    }

    public function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->pageId;
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->pageTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::getPageSecurityContext(static::getResourceWebspaceKey());
    }
}
