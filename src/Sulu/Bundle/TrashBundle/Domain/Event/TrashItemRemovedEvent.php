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
    /**
     * @var int
     */
    private $trashItemId;

    /**
     * @var string
     */
    private $trashItemResourceKey;

    /**
     * @var string
     */
    private $trashItemResourceId;

    /**
     * @var string|null
     */
    private $trashItemTitle;

    /**
     * @var string|null
     */
    private $trashItemTitleLocale;

    public function __construct(
        int $trashItemId,
        string $trashItemResourceKey,
        string $trashItemResourceId,
        ?string $trashItemTitle,
        ?string $trashItemTitleLocale
    ) {
        parent::__construct();

        $this->trashItemId = $trashItemId;
        $this->trashItemResourceKey = $trashItemResourceKey;
        $this->trashItemResourceId = $trashItemResourceId;
        $this->trashItemTitle = $trashItemTitle;
        $this->trashItemTitleLocale = $trashItemTitleLocale;
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
