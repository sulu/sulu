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

namespace Sulu\Bundle\TrashBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Infrastructure\Sulu\Admin\TrashAdmin;

class TrashItemRemovedEvent extends DomainEvent
{
    public function __construct(
        private int $trashItemId,
        private string $trashItemResourceKey,
        private string $trashItemResourceId,
        private ?string $trashItemTitle,
        private ?string $trashItemTitleLocale
    ) {
        parent::__construct();
    }

    public function getTrashItemResourceKey(): string
    {
        return $this->trashItemResourceKey;
    }

    public function getTrashItemResourceId(): string
    {
        return $this->trashItemResourceId;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return TrashItemInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->trashItemId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->trashItemTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->trashItemTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return TrashAdmin::SECURITY_CONTEXT;
    }
}
