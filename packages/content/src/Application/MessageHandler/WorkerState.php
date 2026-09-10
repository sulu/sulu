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

namespace Sulu\Content\Application\MessageHandler;

/**
 * Tells a handler whether it runs on a `messenger:consume` worker, which a handler cannot see itself
 * because it is given the message, not the envelope.
 *
 * @internal
 */
final class WorkerState
{
    private bool $running = false;

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function onWorkerStarted(): void
    {
        $this->running = true;
    }

    public function onWorkerStopped(): void
    {
        $this->running = false;
    }
}
