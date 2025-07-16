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

use Sulu\Page\Application\Message\MovePageMessage;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a PageMapper to extend this Handler.
 */
class MovePageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
    ) {
    }

    public function __invoke(MovePageMessage $message): void
    {
        $this->pageRepository->moveOneBy($message->getIdentifier(), $message->getTargetParentIdentifier());
    }
}
