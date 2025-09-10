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
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;

class PageMovedEvent extends DomainEvent
{
    public function __construct(
        private PageInterface $page,
        private string $locale,
        private ?string $previousParentId,
        private ?string $previousParentWebspaceKey,
        private ?string $previousParentTitle,
    ) {
        parent::__construct();
    }

    public function getPage(): PageInterface
    {
        return $this->page;
    }

    public function getEventType(): string
    {
        return 'moved';
    }

    public function getEventContext(): array
    {
        $eventContext = [
            'previousParentId' => $this->previousParentId,
            'previousParentWebspaceKey' => $this->previousParentWebspaceKey,
            'previousParentTitle' => $this->previousParentTitle,
            'previousParentTitleLocale' => $this->locale,
            'newParentId' => null,
            'newParentWebspaceKey' => null,
            'newParentTitle' => null,
            'newParentTitleLocale' => null,
        ];

        /** @var PageInterface|null $newParent */
        $newParent = $this->page->getParent();

        if (!$newParent) {
            return $eventContext;
        }

        $newParentDimensionContentCollection = new DimensionContentCollection($newParent->getDimensionContents()->toArray(), [], PageDimensionContent::class);
        $newParentLocalizedDimensionContent = $newParentDimensionContentCollection->getDimensionContent(['locale' => $this->locale]);

        return \array_merge($eventContext, [
            'newParentId' => $newParent->getUuid(),
            'newParentWebspaceKey' => $newParent->getWebspaceKey(),
            'newParentTitle' => $newParentLocalizedDimensionContent?->getTitle(),
            'newParentTitleLocale' => $this->locale,
        ]);
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
        $dimensionContentCollection = new DimensionContentCollection($this->page->getDimensionContents()->toArray(), [], PageDimensionContent::class);

        return $dimensionContentCollection->getDimensionContent(['locale' => $this->locale])?->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::getPageSecurityContext(static::getResourceWebspaceKey());
    }
}
