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
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\WebspaceSettingAdmin;

abstract class AbstractWebspaceSettingEvent extends DomainEvent
{
    public function __construct(
        private WebspaceSettingInterface $webspaceSetting,
        private string $locale,
    ) {
        parent::__construct();
    }

    public function getResourceKey(): string
    {
        return WebspaceSettingInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->webspaceSetting->getId();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->webspaceSetting->getWebspaceKey();
    }

    public function getResourceTitle(): ?string
    {
        return $this->webspaceSetting->getWebspaceKey();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return WebspaceSettingAdmin::getSecurityContext($this->webspaceSetting->getWebspaceKey());
    }
}
