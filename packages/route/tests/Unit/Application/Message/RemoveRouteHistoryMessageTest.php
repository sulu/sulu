<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Application\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Route\Application\Message\RemoveRouteHistoryMessage;

#[CoversClass(RemoveRouteHistoryMessage::class)]
class RemoveRouteHistoryMessageTest extends TestCase
{
    public function testGetIdentifier(): void
    {
        $identifier = ['id' => 2];
        $message = $this->createMessage($identifier);

        $this->assertSame($identifier, $message->getIdentifier());
    }

    /**
     * @param array{id?: int} $identifier
     */
    private function createMessage(
        array $identifier = ['id' => 1],
    ): RemoveRouteHistoryMessage {
        return new RemoveRouteHistoryMessage($identifier);
    }
}
