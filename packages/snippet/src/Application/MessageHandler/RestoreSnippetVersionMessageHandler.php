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
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Application\Message\RestoreSnippetVersionMessage;
use Sulu\Snippet\Domain\Event\SnippetVersionRestoredEvent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
class RestoreSnippetVersionMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentCopierInterface $contentCopier,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    public function __invoke(RestoreSnippetVersionMessage $message): SnippetInterface
    {
        $options = $message->getOptions();
        $stage = $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT;

        $snippet = $this->snippetRepository->getOneBy(
            $message->getSnippetIdentifier(),
            [
                SnippetRepositoryInterface::SELECT_SNIPPET_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true],
                    'dimensionAttributes' => [
                        'locale' => $message->getLocale(),
                        'stage' => $stage,
                        'version' => [$message->getVersion(), DimensionContentInterface::CURRENT_VERSION],
                    ],
                ],
            ]
        );

        $dimensionContent = $this->contentCopier->copy(
            $snippet,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => $message->getVersion(),
            ],
            $snippet,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            [
                'ignoredAttributes' => ['url'],
            ]
        );

        $this->domainEventCollector->collect(new SnippetVersionRestoredEvent($snippet, $message->getLocale(), $message->getVersion()));

        return $dimensionContent->getResource();
    }
}
