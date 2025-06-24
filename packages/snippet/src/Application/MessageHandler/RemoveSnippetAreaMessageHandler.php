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
use Sulu\Snippet\Application\Message\RemoveSnippetAreaMessage;
use Sulu\Snippet\Domain\Model\SnippetArea;

class RemoveSnippetAreaMessageHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(RemoveSnippetAreaMessage $message): void
    {
        $snippetAreaRepository = $this->entityManager->getRepository(SnippetArea::class);
        $entityToDelete = $snippetAreaRepository->findOneBy([
            'webspaceKey' => $message->getWebspaceKey(),
            'areaKey' => $message->getAreaKey(),
        ]);

        $this->entityManager->remove($entityToDelete);
    }
}
