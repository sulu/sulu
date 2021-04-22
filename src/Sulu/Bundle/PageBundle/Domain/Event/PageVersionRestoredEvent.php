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

class PageVersionRestoredEvent extends DomainEvent
{
    /**
     * @var BasePageDocument
     */
    private $pageDocument;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $version;

    public function __construct(
        BasePageDocument $pageDocument,
        string $locale,
        string $version
    ) {
        parent::__construct();

        $this->pageDocument = $pageDocument;
        $this->locale = $locale;
        $this->version = $version;
    }

    public function getPageDocument(): BasePageDocument
    {
        return $this->pageDocument;
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
        return PageInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->pageDocument->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
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
}
