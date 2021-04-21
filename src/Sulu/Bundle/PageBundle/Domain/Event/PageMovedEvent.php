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
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Domain\PageInterface;

class PageMovedEvent extends DomainEvent
{
    /**
     * @var BasePageDocument
     */
    private $pageDocument;

    /**
     * @var string|null
     */
    private $previousParentId;

    public function __construct(
        BasePageDocument $pageDocument,
        ?string $previousParentId
    ) {
        parent::__construct();

        $this->pageDocument = $pageDocument;
        $this->previousParentId = $previousParentId;
    }

    public function getPageDocument(): BasePageDocument
    {
        return $this->pageDocument;
    }

    public function getEventType(): string
    {
        return 'moved';
    }

    public function getEventContext(): array
    {
        /** @var BasePageDocument|null $newParent */
        $newParent = $this->pageDocument->getParent();

        return [
            'previousParentId' => $this->previousParentId,
            'newParentId' => $newParent ? $newParent->getUuid() : null,
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

    public function getResourceWebspaceKey(): ?string
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
