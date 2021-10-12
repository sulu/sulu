<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;

class PreviewLinkGeneratedEvent extends DomainEvent
{
    /**
     * @var PreviewLinkInterface
     */
    private $previewLink;

    /**
     * @var string
     */
    private $link;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @var string|null
     */
    private $securityContext;

    /**
     * @param mixed[] $payload
     */
    public function __construct(
        PreviewLinkInterface $previewLink,
        string $link,
        array $payload,
        ?string $securityContext
    ) {
        parent::__construct();

        $this->previewLink = $previewLink;
        $this->link = $link;
        $this->payload = $payload;
        $this->securityContext = $securityContext;
    }

    public function getPreviewLink(): PreviewLinkInterface
    {
        return $this->previewLink;
    }

    public function getEventType(): string
    {
        return 'preview_link_generated';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return $this->previewLink->getResourceKey();
    }

    public function getResourceId(): string
    {
        return $this->previewLink->getResourceId();
    }

    public function getResourceLocale(): string
    {
        return $this->previewLink->getLocale();
    }

    public function getResourceTitle(): ?string
    {
        return $this->link;
    }

    public function getResourceTitleLocale(): ?string
    {
        return null;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->securityContext;
    }
}
