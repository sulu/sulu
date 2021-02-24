<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DomainEventBundle\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\DomainEventBundle\Entity\DomainEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PersistDomainEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            DomainEvent::class => ['persistsDomainEvent', -256],
        ];
    }

    public function persistsDomainEvent(DomainEvent $event)
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
