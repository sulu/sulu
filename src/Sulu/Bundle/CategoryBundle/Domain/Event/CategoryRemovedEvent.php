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
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class CategoryRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $categoryId;

    /**
     * @var string|null
     */
    private $categoryName;

    public function __construct(
        int $categoryId,
        ?string $categoryName
    ) {
        parent::__construct();

        $this->categoryId = $categoryId;
        $this->categoryName = $categoryName;
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
        return $this->categoryName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
