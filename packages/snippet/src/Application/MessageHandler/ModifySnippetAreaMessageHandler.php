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

use Sulu\Snippet\Application\Message\ModifySnippetAreaMessage;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

readonly class ModifySnippetAreaMessageHandler
{
    public function __construct(
        private SnippetAreaRepositoryInterface $snippetAreaRepository,
        private SnippetRepositoryInterface $snippetRepository,
    ) {
    }

    public function __invoke(ModifySnippetAreaMessage $message): SnippetAreaInterface
    {
        $webspaceKey = $message->getWebspace();
        $areaKey = $message->getAreaKey();

        $snippetArea = $this->snippetAreaRepository->findOneBy(['webspaceKey' => $webspaceKey, 'areaKey' => $areaKey]);
        if (null === $snippetArea) {
            $snippetArea = $this->snippetAreaRepository->createNew($areaKey, $webspaceKey);

            $this->snippetAreaRepository->add($snippetArea);
        }

        $snippet = $this->snippetRepository->getOneBy($message->getSnippetIdentifier());
        $snippetArea->setSnippet($snippet);

        return $snippetArea;
    }
}
