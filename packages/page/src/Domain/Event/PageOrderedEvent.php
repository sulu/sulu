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
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;

class PageOrderedEvent extends DomainEvent
{
    public function __construct(
        private Page $page,
        private string $locale,
        private int $targetPosition,
    ) {
        parent::__construct();
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getEventType(): string
    {
        return 'ordered';
    }

    public function getEventContext(): array
    {
        return [
            'targetPosition' => $this->targetPosition,
        ];
    }

    public function getResourceKey(): string
    {
        return Page::RESOURCE_KEY;
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
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $this->locale]);

        return $localizedDimensionContent->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::getPageSecurityContext(static::getResourceWebspaceKey());
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return SecurityBehavior::class;
    }
}
