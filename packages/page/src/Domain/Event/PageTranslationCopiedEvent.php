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

class PageTranslationCopiedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private PageInterface $page,
        private string $locale,
        private string $sourceLocale,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getPage(): PageInterface
    {
        return $this->page;
    }

    public function getEventType(): string
    {
        return 'translation_copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourceLocale' => $this->sourceLocale,
        ];
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->page->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
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
