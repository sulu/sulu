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

class SnippetCopiedEvent extends DomainEvent
{
    public function __construct(
        private SnippetInterface $snippet,
        private string $sourceSnippetId,
        private ?string $sourceSnippetTitle,
        private ?string $sourceSnippetTitleLocale,
    ) {
        parent::__construct();
    }

    public function getSnippet(): SnippetInterface
    {
        return $this->snippet;
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
        return SnippetInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->snippet->getUuid();
    }

    public function getResourceTitle(): ?string
    {
        if (null === $this->sourceSnippetTitleLocale) {
            return null;
        }

        $dimensionContentCollection = new DimensionContentCollection($this->snippet->getDimensionContents(), [], SnippetDimensionContent::class);

        return $dimensionContentCollection->getDimensionContent(['locale' => $this->sourceSnippetTitleLocale])?->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->sourceSnippetTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SnippetAdmin::SECURITY_CONTEXT;
    }
}
