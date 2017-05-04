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
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\UserContextSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\UserContext\UserContextStoreInterface;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    public function setUp()
    {
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->userContextStore = $this->prophesize(UserContextStoreInterface::class);
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
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
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
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

        $this->userContextStore->setUserContext($result)->shouldBeCalled();
    }

    public function provideSetUserContext()
    {
        return [
            ['X-User-Context', 'user-context', '1', null, '1'],
            ['X-Context', 'user-context', '2', null, '2'],
            ['X-User-Context', 'user-context', null, '1', '1'],
            ['X-User-Context', 'context', null, '3', '3'],
            ['X-User-Context', 'user-context', '1', '2', '1'],
        ];
    }

    /**
     * @dataProvider provideSetUserContextWithoutCookie
     */
    public function testSetUserContextWithoutCookie($evaluatedTargetGroup, $result)
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-User-Context',
            'user-context'
        );

        $event = $this->prophesize(GetResponseEvent::class);
        $request = new Request();
        $event->getRequest()->willReturn($request);

        $this->targetGroupEvaluator->evaluate()->willReturn($evaluatedTargetGroup);

        $this->userContextStore->setUserContext(Argument::any())->shouldNotBeCalled();
        $this->userContextStore->updateUserContext($result)->shouldBeCalled();

        $userContextSubscriber->setUserContext($event->reveal());

        $this->assertCount(0, $request->headers->all());
    }

    public function provideSetUserContextWithoutCookie()
    {
        $targetGroup1 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup1->getId()->willReturn(1);

        $targetGroup2 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup2->getId()->willReturn(3);

        return [
            [$targetGroup1->reveal(), 1],
            [$targetGroup2->reveal(), 3],
            [null, 0],
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
            $this->targetGroupEvaluator->reveal(),
            $contextUrl,
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
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
     * @dataProvider provideAddSetCookieHeader
     */
    public function testAddSetCookieHeader($userContextCookie, $hasChanged, $cookieValue)
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-URL',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-User-Context',
            $userContextCookie
        );

        $this->userContextStore->hasChanged()->willReturn($hasChanged);
        $this->userContextStore->getUserContext()->willReturn($cookieValue);

        $event = $this->prophesize(FilterResponseEvent::class);
        $response = new Response();
        $event->getResponse()->willReturn($response);

        $userContextSubscriber->addSetCookieHeader($event->reveal());

        if ($cookieValue) {
            $cookie = $response->headers->getCookies()[0];
            $this->assertEquals($userContextCookie, $cookie->getName());
            $this->assertEquals($cookieValue, $cookie->getValue());
        } else {
            $this->assertCount(0, $response->headers->getCookies());
        }
    }

    public function provideAddSetCookieHeader()
    {
        return [
            ['user-cookie', false, null],
            ['user-cookie', true, 1],
            ['user-cookie', true, 2],
        ];
    }

    /**
     * @dataProvider provideAddUserContextHitScript
     */
    public function testAddUserContextHitScript(
        $contextHitUrl,
        $forwardedUrlHeader,
        $forwardedRefererHeader,
        $forwardedUuidHeader,
        $uuid
    ) {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            $contextHitUrl,
            $forwardedUrlHeader,
            $forwardedRefererHeader,
            $forwardedUuidHeader,
            'X-User-Context',
            'user-cookie'
        );

        $event = $this->prophesize(FilterResponseEvent::class);

        $request = new Request();
        if ($uuid) {
            $structureBridge = $this->prophesize(StructureBridge::class);
            $structureBridge->getUuid()->willReturn($uuid);
            $request->attributes->set('structure', $structureBridge->reveal());
        }
        $event->getRequest()->willReturn($request);

        $response = new Response('<body></body>');
        $response->headers->set('Content-Type', 'text/html');
        $event->getResponse()->willReturn($response);

        $this->twig->render('SuluAudienceTargetingBundle:Template:hit-script.html.twig', [
            'url' => $contextHitUrl,
            'urlHeader' => $forwardedUrlHeader,
            'refererHeader' => $forwardedRefererHeader,
            'uuidHeader' => $forwardedUuidHeader,
            'uuid' => $uuid,
        ])->willReturn('<script></script>');

        $userContextSubscriber->addUserContextHitScript($event->reveal());

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }

    public function provideAddUserContextHitScript()
    {
        return [
            ['/_user_context_hit', 'X-Forwarded-URL', 'X-Fowarded-Referer', 'X-Forwarded-UUID', 'some-uuid'],
            ['/_user_context_hit', 'X-Forwarded-URL', 'X-Fowarded-Referer', 'X-Forwarded-UUID', null],
            ['/context_hit', 'X-Other-URL', 'X-Other-Referer', 'X-Uuid', 'some-other-uuid'],
        ];
    }

    public function testAddUserContextHitScriptInPreview()
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            true,
            $this->userContextStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-User-Context',
            'user-cookie'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $event->getRequest()->willReturn($request);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $userContextSubscriber->addUserContextHitScript($event->reveal());

        $this->assertEquals('', $response->getContent());
    }

    public function testAddUserContextHitScriptNonHtml()
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-User-Context',
            'user-cookie'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $event->getRequest()->willReturn($request);
        $response = new JsonResponse();
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $userContextSubscriber->addUserContextHitScript($event->reveal());

        $this->assertEquals('{}', $response->getContent());
    }

    public function testAddUserContextHitScriptHtmlUtf8()
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwared-Referer',
            'X-Fowarded-UUID',
            'X-User-Context',
            'user-cookie'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $event->getRequest()->willReturn($request);
        $response = new Response('<body></body>');
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->willReturn('<script></script>');

        $userContextSubscriber->addUserContextHitScript($event->reveal());

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }

    public function testAddUserContextHitScriptNonGet()
    {
        $userContextSubscriber = new UserContextSubscriber(
            $this->twig->reveal(),
            false,
            $this->userContextStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            '/_user_context',
            '/_user_context_hit',
            'X-Forwarded-Url',
            'X-Forwared-Referer',
            'X-Forwarded-UUID',
            'X-User-Context',
            'user-cookie'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $event->getRequest()->willReturn($request);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $userContextSubscriber->addUserContextHitScript($event->reveal());

        $this->assertEquals('', $response->getContent());
    }
}
