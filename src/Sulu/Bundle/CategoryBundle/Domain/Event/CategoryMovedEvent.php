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

use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class CategoryMovedEvent extends DomainEvent
{
    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var int|null
     */
    private $previousParentId;

    public function __construct(
        CategoryInterface $category,
        ?int $previousParentId
    ) {
        parent::__construct();

        $this->category = $category;
        $this->previousParentId = $previousParentId;
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function getEventType(): string
    {
        return 'moved';
    }

    public function getEventContext(): array
    {
        $previousParent = $this->category->getParent();

        return [
            'previousParentId' => $this->previousParentId,
            'newParentId' => $previousParent ? $previousParent->getId() : null,
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
        $translation = $this->getCategoryTranslation();

        return $translation ? $translation->getTranslation() : null;
    }

    public function getResourceTitleLocale(): ?string
    {
        $translation = $this->getCategoryTranslation();

        return $translation ? $translation->getLocale() : null;
    }

    private function getCategoryTranslation(): ?CategoryTranslationInterface
    {
        return $this->category->findTranslationByLocale($this->category->getDefaultLocale()) ?: null;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
