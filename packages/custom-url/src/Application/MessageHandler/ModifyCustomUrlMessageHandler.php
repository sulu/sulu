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
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Application\Messages\ModifyCustomUrlMessage;
use Sulu\CustomUrl\Domain\Event\CustomUrlModifiedEvent;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ModifyCustomUrlMessageHandler
{
    public function __construct(
        private readonly CustomUrlMapperInterface $mapper,
        private readonly CustomUrlRepositoryInterface $customUrlRepository,
        private readonly DomainEventCollectorInterface $documentDomainEventCollector,
    ) {
    }

    public function __invoke(ModifyCustomUrlMessage $message): CustomUrlInterface
    {
        $data = $message->getData();

        $customUrl = $this->customUrlRepository->find($message->getUuid());
        if (null === $customUrl) {
            throw new EntityNotFoundException('No entity with id ' . $message->getUuid());
        }

        if ($customUrl->getWebspace() !== $message->getWebspaceKey()) {
            throw new AccessDeniedException(\sprintf('Entity from webspace "%s" does not belong to webspace "%s"', $customUrl->getWebspace(), $message->getWebspaceKey()));
        }

        $this->mapper->mapCustomUrlData($customUrl, $data);

        $this->customUrlRepository->add($customUrl);

        $this->documentDomainEventCollector->collect(new CustomUrlModifiedEvent($customUrl, $data));

        return $customUrl;
    }
}
