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

namespace Sulu\Snippet\Application\MessageHandler;

use Sulu\Snippet\Application\Message\RemoveSnippetAreaMessage;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;

readonly class RemoveSnippetAreaMessageHandler
{
    public function __construct(
        private SnippetAreaRepositoryInterface $snippetAreaRepository,
    ) {
    }

    public function __invoke(RemoveSnippetAreaMessage $message): ?SnippetAreaInterface
    {
        $snippetArea = $this->snippetAreaRepository->findOneBy([
            'webspaceKey' => $message->getWebspaceKey(),
            'areaKey' => $message->getAreaKey(),
        ]);

        if (null === $snippetArea) {
            return null;
        }

        $this->snippetAreaRepository->remove($snippetArea);

        $snippetArea->setSnippet(null);

        return $snippetArea;
    }
}
