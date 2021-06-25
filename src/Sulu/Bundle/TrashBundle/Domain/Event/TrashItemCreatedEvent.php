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

class TrashItemCreatedEvent extends DomainEvent
{
    /**
     * @var TrashItemInterface
     */
    private $trashItem;

    public function __construct(TrashItemInterface $trashItem)
    {
        parent::__construct();

        $this->trashItem = $trashItem;
    }

    public function getTrashItem(): TrashItemInterface
    {
        return $this->trashItem;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getResourceKey(): string
    {
        return TrashItemInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->trashItem->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->trashItem->getTranslation()->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->trashItem->getTranslation()->getLocale();
    }

    public function getResourceSecurityContext(): ?string
    {
        return TrashAdmin::SECURITY_CONTEXT;
    }
}
