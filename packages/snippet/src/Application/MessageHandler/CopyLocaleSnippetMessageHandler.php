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
use Sulu\Snippet\Application\Message\CopyLocaleSnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetTranslationCopiedEvent;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyLocaleSnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentCopierInterface $contentCopier,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(CopyLocaleSnippetMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());

        $this->contentCopier->copy(
            $snippet,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getSourceLocale(),
            ],
            $snippet,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getTargetLocale(),
            ]
        );

        $this->domainEventCollector->collect(new SnippetTranslationCopiedEvent($snippet, $message->getTargetLocale(), $message->getSourceLocale(), []));
    }
}
