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
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;

class PageMovedEvent extends DomainEvent
{
    /**
     * @var PageDocument
     */
    private $pageDocument;

    /**
     * @var string|null
     */
    private $previousParentId;

    /**
     * @var string|null
     */
    private $previousParentWebspaceKey;

    /**
     * @var string|null
     */
    private $previousParentTitle;

    /**
     * @var string|null
     */
    private $previousParentTitleLocale;

    public function __construct(
        PageDocument $pageDocument,
        ?string $previousParentId,
        ?string $previousParentWebspaceKey,
        ?string $previousParentTitle,
        ?string $previousParentTitleLocale
    ) {
        parent::__construct();

        $this->pageDocument = $pageDocument;
        $this->previousParentId = $previousParentId;
        $this->previousParentWebspaceKey = $previousParentWebspaceKey;
        $this->previousParentTitle = $previousParentTitle;
        $this->previousParentTitleLocale = $previousParentTitleLocale;
    }

    public function getPageDocument(): PageDocument
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
            'previousParentWebspaceKey' => $this->previousParentWebspaceKey,
            'previousParentTitle' => $this->previousParentTitle,
            'previousParentTitleLocale' => $this->previousParentTitleLocale,
            'newParentId' => $newParent ? $newParent->getUuid() : null,
            'newParentWebspaceKey' => $newParent ? $newParent->getWebspaceName() : null,
            'newParentTitle' => $newParent ? $newParent->getTitle() : null,
            'newParentTitleLocale' => $newParent ? $newParent->getLocale() : null,
        ];
    }

    public function getResourceKey(): string
    {
        return BasePageDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->pageDocument->getUuid();
    }

    public function getResourceWebspaceKey(): string
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

    public function getResourceSecurityObjectType(): ?string
    {
        return SecurityBehavior::class;
    }
}
