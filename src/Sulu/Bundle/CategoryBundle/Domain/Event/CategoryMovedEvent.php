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
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

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
        return [
            'previousParentId' => $this->previousParentId,
            'newParentId' => $this->category->getParentId(),
        ];
    }

    public function getResourceKey(): string
    {
        return 'categories';
    }

    public function getResourceId(): string
    {
        return (string) $this->category->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->category->findTranslationByLocale($this->category->getDefaultLocale())->getTranslation();
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
