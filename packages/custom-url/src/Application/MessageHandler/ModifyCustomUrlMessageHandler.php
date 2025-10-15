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
use Sulu\CustomUrl\Application\Messages\ModifyCustomUrlMessage;
use Sulu\CustomUrl\Domain\Event\CustomUrlModifiedEvent;
use Sulu\CustomUrl\Domain\Exception\CustomUrlAlreadyExistsException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ModifyCustomUrlMessageHandler
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

    public function __invoke(ModifyCustomUrlMessage $message): CustomUrlInterface
    {
        $data = $message->getData();

        $customUrl = $this->customUrlRepository->getOneBy(['uuid' => $message->getUuid()]);

        if ($customUrl->getWebspace() !== $message->getWebspaceKey()) {
            throw new AccessDeniedException(\sprintf('Entity from webspace "%s" does not belong to webspace "%s"', $customUrl->getWebspace(), $message->getWebspaceKey()));
        }

        foreach ($this->customUrlMappers as $customUrlMapper) {
            $customUrlMapper->mapCustomUrlData($customUrl, $data);
        }

        // Check if a custom URL with the same title already exists (excluding the current one)
        $title = $customUrl->getTitle();
        $existingCustomUrl = $this->customUrlRepository->findOneBy(['title' => $title]);
        if (null !== $existingCustomUrl && $existingCustomUrl->getUuid() !== $customUrl->getUuid()) {
            throw new CustomUrlAlreadyExistsException($title);
        }

        $this->customUrlRepository->add($customUrl);

        $this->documentDomainEventCollector->collect(new CustomUrlModifiedEvent($customUrl, $data));

        return $customUrl;
    }
}
