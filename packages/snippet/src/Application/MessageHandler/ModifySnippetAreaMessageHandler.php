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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Snippet\Application\Message\ModifySnippetAreaMessage;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

class ModifySnippetAreaMessageHandler
{
    public function __construct(
        private readonly SnippetAreaRepositoryInterface $snippetAreaRepository,
        private readonly SnippetRepositoryInterface $snippetRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ModifySnippetAreaMessage $message): void
    {
        $webspaceKey = $message->getWebspace();
        $areaKey = $message->getAreaKey();

        $snippetArea = $this->snippetAreaRepository->findOneByWebspaceAndKey($webspaceKey, $areaKey);
        if (null === $snippetArea) {
            $snippetArea = $this->snippetAreaRepository->createNew(null, $areaKey, $webspaceKey);

            $this->entityManager->persist($snippetArea);
        }

        $snippet = $this->snippetRepository->getOneBy($message->getSnippet());

        $snippetArea->setSnippet($snippet);
    }
}
