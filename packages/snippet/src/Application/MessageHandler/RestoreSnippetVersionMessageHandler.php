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

use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Application\Message\RestoreSnippetVersionMessage;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
class RestoreSnippetVersionMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentCopierInterface $contentCopier
    ) {
    }

    public function __invoke(RestoreSnippetVersionMessage $message): SnippetInterface
    {
        $snippet = $this->snippetRepository->getOneBy($message->getSnippetIdentifier());
        $options = $message->getOptions();

        $dimensionContent = $this->contentCopier->copy(
            $snippet,
            [
                'stage' => $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                'locale' => $options['locale'] ?? null,
                'version' => $message->getVersion(),
            ],
            $snippet,
            [
                'stage' => $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                'locale' => $options['locale'] ?? null,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            [
                'ignoredAttributes' => ['url'],
            ]
        );

        return $dimensionContent->getResource();
    }
}
