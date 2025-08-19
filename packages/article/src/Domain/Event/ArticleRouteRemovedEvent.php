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

namespace Sulu\Article\Domain\Event;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class ArticleRouteRemovedEvent extends DomainEvent
{
    public function __construct(
        private string $articleId,
        private string $articleTitle,
        private string $articleTitleLocale,
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
        return ArticleInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->articleId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->articleTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->articleTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }
}
