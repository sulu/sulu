<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Application\MessageHandler;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Snippet\Application\Mapper\SnippetMapperInterface;
use Sulu\Snippet\Application\Message\CreateSnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetCreatedEvent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a SnippetMapper to extend this Handler.
 */
final class CreateSnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        /** @var iterable<SnippetMapperInterface> */
        private iterable $snippetMappers,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    public function __invoke(CreateSnippetMessage $message): SnippetInterface
    {
        $data = $message->getData();
        $snippet = $this->snippetRepository->createNew($message->getUuid());

        foreach ($this->snippetMappers as $snippetMapper) {
            $snippetMapper->mapSnippetData($snippet, $data);
        }

        $this->snippetRepository->add($snippet);

        /** @var string $locale */
        $locale = $data['locale'];

        $this->domainEventCollector->collect(new SnippetCreatedEvent($snippet, $locale, $data));

        return $snippet;
    }
}
