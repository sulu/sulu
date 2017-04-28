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

use Prophecy\Argument;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\UserContextSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\UserContext\UserContextStoreInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class UserContextSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var UserContextStoreInterface
     */
    private $userContextStore;

    public function setUp()
    {
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->userContextStore = $this->prophesize(UserContextStoreInterface::class);
    }

    /**
     * @dataProvider provideSetUserContext
     */
    public function testSetUserContext(
        $userContextHeader,
        $userContextCookie,
        $headerUserContext,
        $cookieUserContext,
        $result
    ) {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            $userContextHeader,
            $userContextCookie
        );

        $event = $this->prophesize(GetResponseEvent::class);
        $request = new Request();

        if ($cookieUserContext) {
            $request->cookies->set($userContextCookie, $cookieUserContext);
        }

        if ($headerUserContext) {
            $request->headers->set($userContextHeader, $headerUserContext);
        }

        $event->getRequest()->willReturn($request);

        $userContextSubscriber->setUserContext($event->reveal());

        if ($result) {
            $this->assertEquals($result, $request->headers->get($userContextHeader));
            $this->userContextStore->setUserContext($result)->shouldBeCalled();
        } else {
            $this->assertCount(0, $request->headers->all());
            $this->userContextStore->setUserContext(Argument::any())->shouldNotBeCalled();
        }
    }

    public function provideSetUserContext()
    {
        return [
            ['X-User-Context', 'user-context', '1', null, '1'],
            ['X-Context', 'user-context', '2', null, '2'],
            ['X-User-Context', 'user-context', null, '1', '1'],
            ['X-User-Context', 'context', null, '3', '3'],
            ['X-User-Context', 'user-context', '1', '2', '1'],
            ['X-User-Context', 'user-context', null, null, null]
        ];
    }

    /**
     * @dataProvider provideAddVaryHeader
     */
    public function testAddVaryHeader($contextUrl, $requestUrl, $header, $varyHeaders)
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            $contextUrl,
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            $header,
            'user-context'
        );
        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $requestUrl]);
        $response = new Response();
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response);

        $userContextSubscriber->addVaryHeader($event->reveal());

        $this->assertEquals($varyHeaders, $response->getVary());
    }

    public function provideAddVaryHeader()
    {
        return [
            ['/_user_context', '/test', 'X-User-Context-Hash', ['X-User-Context-Hash']],
            ['/_user_context', '/test', 'X-User-Context', ['X-User-Context']],
            ['/_user_context', '/_user_context', 'X-User-Context-Hash', []],
            ['/_user', '/_user', 'X-User-Context-Hash', []],
        ];
    }

    /**
     * @dataProvider provideAddUserContextHitScript
     */
    public function testAddUserContextHitScript($contextHitUrl, $forwardedUrlHeader, $forwardedRefererHeader)
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            '/_user_context',
            $contextHitUrl,
            $forwardedUrlHeader,
            $forwardedRefererHeader,
            'X-User-Context',
            'user-cookie'
        );
        $event = $this->prophesize(FilterResponseEvent::class);
        $response = new Response('<body></body>');
        $event->getResponse()->willReturn($response);
        $this->twig->render('SuluAudienceTargetingBundle:Template:hit-script.html.twig', [
            'url' => $contextHitUrl,
            'urlHeader' => $forwardedUrlHeader,
            'refererHeader' => $forwardedRefererHeader,
        ])->willReturn('<script></script>');

        $userContextSubscriber->addUserContextHitScript($event->reveal());

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }

    public function provideAddUserContextHitScript()
    {
        return [
            ['/_user_context_hit', 'X-Forwarded-URL', 'X-Fowarded-Referer'],
            ['/context_hit', 'X-Other-URL', 'X-Other-Referer'],
        ];
    }

    public function testAddUserContextHitScriptInPreview()
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            true,
            $this->userContextStore->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwared-Referer',
            'X-User-Context',
            'user-cookie'
        );

        $event = $this->prophesize(FilterResponseEvent::class);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();
        $event->getRequest()->shouldNotBeCalled();
        $event->getResponse()->shouldNotBeCalled();

        $userContextSubscriber->addUserContextHitScript($event->reveal());
    }
}
