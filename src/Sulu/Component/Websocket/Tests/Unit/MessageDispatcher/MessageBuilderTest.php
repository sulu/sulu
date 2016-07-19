<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\Tests\Unit\MessageDispatcher;

use Sulu\Component\Websocket\MessageDispatcher\MessageBuilder;

class MessageBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageBuilderInterface
     */
    private $messageBuilder;

    public function setUp()
    {
        $this->messageBuilder = new MessageBuilder();
    }

    public function testBuild()
    {
        $this->assertEquals(
            [
                'handler' => 'sulu',
                'message' => [
                    'key' => 'value',
                ],
                'options' => [
                    'option' => 'value',
                ],
                'error' => false,
            ],
            json_decode($this->messageBuilder->build('sulu', ['key' => 'value'], ['option' => 'value']), true)
        );
    }
}
