<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit;

use Sulu\Bundle\AudienceTargetingBundle\EventListener\UserContextSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class UserContextSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideConfiguration
     */
    public function testAddUserContextHeaders($contextUrl, $requestUrl, $header, $varyHeaders)
    {
        $userContextSubscriber = new UserContextSubscriber($contextUrl, $header);
        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $requestUrl]);
        $response = new Response();
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response);

        $userContextSubscriber->addUserContextHeaders($event->reveal());

        $this->assertEquals($varyHeaders, $response->getVary());
    }

    public function provideConfiguration()
    {
        return [
            ['/_user_context', '/test', 'X-User-Context-Hash', ['X-User-Context-Hash']],
            ['/_user_context', '/test', 'X-User-Context', ['X-User-Context']],
            ['/_user_context', '/_user_context', 'X-User-Context-Hash', []],
            ['/_user', '/_user', 'X-User-Context-Hash', []],
        ];
    }
}
