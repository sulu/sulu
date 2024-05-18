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

class CategoryMovedEvent extends DomainEvent
{
    public function __construct(
        private CategoryInterface $category,
        private ?int $previousParentId,
        private ?string $previousParentTitle,
        private ?string $previousParentTitleLocale
    ) {
        parent::__construct();
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function getPreviousParentId(): ?int
    {
        return $this->previousParentId;
    }

    public function getPreviousParentTitle(): ?string
    {
        return $this->previousParentTitle;
    }

    public function getPreviousParentTitleLocale(): ?string
    {
        return $this->previousParentTitleLocale;
    }

    public function getEventType(): string
    {
        return 'moved';
    }

    public function getEventContext(): array
    {
        $previousParentTitle = null !== $this->previousParentId ? $this->previousParentTitle : 'ROOT';
        $previousParentTitleLocale = null !== $this->previousParentId ? $this->previousParentTitleLocale : null;

        $newParent = $this->category->getParent();
        $newParentId = $newParent ? $newParent->getId() : null;
        $newParentTranslation = $newParent ? $this->getCategoryTranslation($newParent) : null;
        $newParentTitle = $newParentId ? ($newParentTranslation ? $newParentTranslation->getTranslation() : null) : 'ROOT';
        $newParentTitleLocale = $newParentTranslation ? $newParentTranslation->getLocale() : null;

        return [
            'previousParentId' => $this->previousParentId,
            'previousParentTitle' => $previousParentTitle,
            'previousParentTitleLocale' => $previousParentTitleLocale,
            'newParentId' => $newParentId,
            'newParentTitle' => $newParentTitle,
            'newParentTitleLocale' => $newParentTitleLocale,
        ];
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
