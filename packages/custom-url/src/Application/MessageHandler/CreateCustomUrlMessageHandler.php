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

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Application\Messages\CreateCustomUrlMessage;
use Sulu\CustomUrl\Domain\Event\CustomUrlCreatedEvent;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;

final class CreateCustomUrlMessageHandler
{
    /**
     * @param iterable<CustomUrlMapperInterface> $customUrlMappers
     */
    public function __construct(
        private readonly iterable $customUrlMappers,
        private readonly CustomUrlRepositoryInterface $customUrlRepository,
        private readonly DomainEventCollectorInterface $documentDomainEventCollector,
    ) {
    }

    public function __invoke(CreateCustomUrlMessage $message): CustomUrlInterface
    {
        $data = $message->getData();

        $customUrl = $this->customUrlRepository->create();

        foreach ($this->customUrlMappers as $customUrlMapper) {
            $customUrlMapper->mapCustomUrlData($customUrl, $data);
        }

        $customUrl->setWebspace($message->getWebspaceKey());

        $this->customUrlRepository->add($customUrl);

        $this->documentDomainEventCollector->collect(new CustomUrlCreatedEvent($customUrl, $data));

        return $customUrl;
    }
}
