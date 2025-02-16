<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\MessageHandler;

use Sulu\Page\Application\Message\OrderPageMessage;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class OrderPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
    ) {
    }

    public function __invoke(OrderPageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());

        $parent = $page->getParent();
        $siblings = $this->pageRepository->getChildren($parent);
        $currentPosition = \array_search($page, $siblings);

        if (false === $currentPosition) {
            throw new \RuntimeException('Node not found in sibling list');
        }

        $currentPosition = \intval($currentPosition);
        $movementSteps = $currentPosition - \max(0, $message->getPosition() - 1);

        if ($movementSteps > 0) {
            $this->pageRepository->moveUp($page, $movementSteps);
        } elseif ($movementSteps < 0) {
            $this->pageRepository->moveDown($page, \abs($movementSteps));
        }

        if (true !== $this->pageRepository->verify()) {
            $this->pageRepository->recover();
        }
    }
}
