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

namespace Sulu\Notifier\Tests\Application\Notifier;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\TransportInterface;

final class RecordingTransport implements TransportInterface
{
    /** @var list<MessageInterface> */
    public array $sent = [];

    public ?\Throwable $throwOnSend = null;

    public function send(MessageInterface $message): SentMessage
    {
        if (null !== $this->throwOnSend) {
            throw $this->throwOnSend;
        }
        $this->sent[] = $message;

        return new SentMessage($message, (string) $this);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage;
    }

    public function __toString(): string
    {
        return 'recording://test';
    }
}
