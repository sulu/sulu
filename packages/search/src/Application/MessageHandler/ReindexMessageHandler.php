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

namespace Sulu\Search\Application\MessageHandler;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;

/**
 * @internal To call this service always use the message bus with the ReindexConfig as a message.
 *           Direct calls are not supported we may move the service at any time.
 */
final readonly class ReindexMessageHandler
{
    /**
     * @param iterable<ReindexProviderInterface> $reindexProviders
     */
    public function __construct(
        private EngineInterface $engine,
        private iterable $reindexProviders,
    ) {
    }

    public function __invoke(ReindexConfig $reindexConfig): void
    {
        $this->engine->reindex(
            $this->reindexProviders,
            $reindexConfig,
        );
    }
}
