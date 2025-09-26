<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;

class CategoryRemovedEvent extends DomainEvent
{
    /**
     * @param string[]|null $allLocales
     */
    public function __construct(
        private int $categoryId,
        private ?string $categoryTitle,
        private ?string $categoryTitleLocale,
        private ?array $allLocales,
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return CategoryInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->categoryId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->categoryTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->categoryTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }

    /**
     * @return string[]|null
     */
    public function getAllLocales(): ?array
    {
        return $this->allLocales;
    }
}
