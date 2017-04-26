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
     * @var \Twig_Environment
     */
    private $twig;

    public function setUp()
    {
        $this->twig = $this->prophesize(\Twig_Environment::class);
    }

    /**
     * @dataProvider provideAddUserContextHeaders
     */
    public function testAddUserContextHeaders($contextUrl, $requestUrl, $header, $varyHeaders)
    {
        $userContextSubscriber = new UserContextSubscriber($this->twig->reveal(), $contextUrl, $header);
        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $requestUrl]);
        $response = new Response();
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response);

        $userContextSubscriber->addUserContextHeaders($event->reveal());

        $this->assertEquals($varyHeaders, $response->getVary());
    }

    public function provideAddUserContextHeaders()
    {
        return [
            ['/_user_context', '/test', 'X-User-Context-Hash', ['X-User-Context-Hash']],
            ['/_user_context', '/test', 'X-User-Context', ['X-User-Context']],
            ['/_user_context', '/_user_context', 'X-User-Context-Hash', []],
            ['/_user', '/_user', 'X-User-Context-Hash', []],
        ];
    }

    public function testAddUserContextHitScript()
    {
        $userContextSubscriber = new UserContextSubscriber($this->twig->reveal(), '/_user_context', 'X-User-Context');
        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $response = new Response('<body></body>');
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response);
        $this->twig->render('SuluAudienceTargetingBundle:Template:hit-script.html.twig')->willReturn('<script></script>');

        $userContextSubscriber->addUserContextHitScript($event->reveal());

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }
}
