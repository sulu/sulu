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

namespace Sulu\Snippet\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;

class SnippetVersionRestoredEvent extends DomainEvent
{
    public function __construct(
        private SnippetInterface $snippet,
        private string $locale,
        private int $version
    ) {
        parent::__construct();
    }

    public function getSnippet(): SnippetInterface
    {
        return $this->snippet;
    }

    public function getEventType(): string
    {
        return 'version_restored';
    }

    public function getEventContext(): array
    {
        return [
            'version' => $this->version,
        ];
    }

    public function getResourceKey(): string
    {
        return SnippetInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->snippet->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        $dimensionContentCollection = new DimensionContentCollection($this->snippet->getDimensionContents()->toArray(), [], SnippetDimensionContent::class);

        return $dimensionContentCollection->getDimensionContent(['locale' => $this->locale])?->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::SECURITY_CONTEXT;
    }
}
