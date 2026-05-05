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
use Sulu\Content\Application\ContentHash\ContentHashChecker;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Application\Mapper\SnippetMapperInterface;
use Sulu\Snippet\Application\Message\ModifySnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetModifiedEvent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create a SnippetMapper to extend this Handler.
 */
final class ModifySnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        /** @var iterable<SnippetMapperInterface> */
        private iterable $snippetMappers,
        private DomainEventCollectorInterface $domainEventCollector,
        private ContentHashChecker $contentHashChecker,
    ) {
    }

    public function __invoke(ModifySnippetMessage $message): SnippetInterface
    {
        $identifier = $message->getIdentifier();
        $data = $message->getData();
        /** @var string $locale */
        $locale = $data['locale'];

        $snippet = $this->snippetRepository->getOneBy(
            [
                ...$identifier,
                'locale' => $locale,
            ],
            [
                SnippetRepositoryInterface::SELECT_SNIPPET_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ]
        );

        $this->contentHashChecker->checkHash(
            $data,
            $snippet,
            ['locale' => $locale, 'stage' => DimensionContentInterface::STAGE_DRAFT],
            $snippet->getId(),
        );

        foreach ($this->snippetMappers as $snippetMapper) {
            $snippetMapper->mapSnippetData($snippet, $data);
        }

        $this->domainEventCollector->collect(new SnippetModifiedEvent($snippet, $locale, $data));

        return $snippet;
    }
}
