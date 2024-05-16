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
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;

class CategoryRestoredEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private CategoryInterface $category,
        private array $payload
    ) {
        parent::__construct();

        $this->category = $category;
        $this->payload = $payload;
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function getEventType(): string
    {
        return 'restored';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return CategoryInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->category->getId();
    }

    public function getResourceTitle(): ?string
    {
        $translation = $this->getCategoryTranslation($this->category);

        return $translation ? $translation->getTranslation() : null;
    }

    public function getResourceTitleLocale(): ?string
    {
        $translation = $this->getCategoryTranslation($this->category);

        return $translation ? $translation->getLocale() : null;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }

    private function getCategoryTranslation(CategoryInterface $category): ?CategoryTranslationInterface
    {
        return $category->findTranslationByLocale($category->getDefaultLocale()) ?: null;
    }
}
