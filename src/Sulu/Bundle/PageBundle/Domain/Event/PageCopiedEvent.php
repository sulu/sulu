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

class PageCopiedEvent extends DomainEvent
{
    /**
     * @var PageDocument
     */
    private $pageDocument;

    /**
     * @var string
     */
    private $sourcePageId;

    /**
     * @var string
     */
    private $sourcePageWebspaceKey;

    /**
     * @var string|null
     */
    private $sourcePageTitle;

    /**
     * @var string|null
     */
    private $sourcePageTitleLocale;

    public function __construct(
        PageDocument $pageDocument,
        string $sourcePageId,
        string $sourcePageWebspaceKey,
        ?string $sourcePageTitle,
        ?string $sourcePageTitleLocale
    ) {
        parent::__construct();

        $this->pageDocument = $pageDocument;
        $this->sourcePageId = $sourcePageId;
        $this->sourcePageWebspaceKey = $sourcePageWebspaceKey;
        $this->sourcePageTitle = $sourcePageTitle;
        $this->sourcePageTitleLocale = $sourcePageTitleLocale;
    }

    public function getPageDocument(): PageDocument
    {
        return $this->pageDocument;
    }

    public function getEventType(): string
    {
        return 'copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourcePageId' => $this->sourcePageId,
            'sourcePageWebspaceKey' => $this->sourcePageWebspaceKey,
            'sourcePageTitle' => $this->sourcePageTitle,
            'sourcePageTitleLocale' => $this->sourcePageTitleLocale,
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
