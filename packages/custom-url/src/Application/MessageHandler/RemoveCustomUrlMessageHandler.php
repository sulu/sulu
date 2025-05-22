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

namespace Sulu\CustomUrl\Application\MessageHandler;

use Doctrine\ORM\EntityNotFoundException;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\CustomUrl\Application\Messages\RemoveCustomUrlMessage;
use Sulu\CustomUrl\Domain\Event\CustomUrlRemovedEvent;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class RemoveCustomUrlMessageHandler
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private DomainEventCollectorInterface $documentDomainEventCollector
    ) {
    }

    public function __invoke(RemoveCustomUrlMessage $message): void
    {
        $customUrl = $this->customUrlRepository->find($message->getUuid());
        if (null === $customUrl) {
            throw new EntityNotFoundException('No entity with id ' . $message->getUuid());
        }

        if ($customUrl->getWebspace() !== $message->getWebspaceKey()) {
            throw new AccessDeniedException(\sprintf('Entity from webspace "%s" does not belong to webspace "%s"', $customUrl->getWebspace(), $message->getWebspaceKey()));
        }

        $this->documentDomainEventCollector->collect(new CustomUrlRemovedEvent($customUrl));

        $this->customUrlRepository->remove($customUrl);
    }
}
