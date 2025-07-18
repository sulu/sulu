<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\MessageHandler;

use Sulu\Content\Application\ContentRestorer\ContentVersionRestorerInterface;
use Sulu\Content\Application\Message\RestoreContentVersionMessage;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 * @template T of ContentRichEntityInterface
 */
class RestoreContentVersionMessageHandler
{
    /**
     * @param ServiceLocator<ContentVersionRestorerInterface> $contentRestorers
     */
    public function __construct(private ServiceLocator $contentRestorers)
    {
    }

    /**
     * @return ContentRichEntityInterface<T>
     */
    public function __invoke(RestoreContentVersionMessage $message): ContentRichEntityInterface
    {
        $contentRestorer = $this->contentRestorers->get($message->getResourceKey());

        return $contentRestorer->restore(
            $message->getContentRichEntityIdentifier(),
            $message->getVersion(),
            $message->getOptions()
        );
    }
}
