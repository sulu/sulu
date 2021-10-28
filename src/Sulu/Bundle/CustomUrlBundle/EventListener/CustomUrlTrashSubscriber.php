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

namespace Sulu\Bundle\CustomUrlBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class CustomUrlTrashSubscriber implements EventSubscriberInterface
{
    /**
     * @var TrashManagerInterface
     */
    private $trashManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var bool
     */
    private $hasPendingTrashItem = false;

    public function __construct(
        TrashManagerInterface $trashManager,
        EntityManagerInterface $entityManager
    ) {
        $this->trashManager = $trashManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::REMOVE => ['storeCustomUrlToTrash', 1024],
            Events::FLUSH => 'flushTrashItem',
            Events::CLEAR => 'clearPendingTrashItem',
        ];
    }

    public function storeCustomUrlToTrash(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof CustomUrlDocument) {
            return;
        }

        $this->trashManager->store(CustomUrlDocument::RESOURCE_KEY, $document);
        $this->hasPendingTrashItem = true;
    }

    public function flushTrashItem(FlushEvent $event): void
    {
        if (!$this->hasPendingTrashItem) {
            return;
        }

        $this->entityManager->flush();
        $this->hasPendingTrashItem = false;
    }

    public function clearPendingTrashItem(ClearEvent $event): void
    {
        $this->hasPendingTrashItem = false;
    }
}
