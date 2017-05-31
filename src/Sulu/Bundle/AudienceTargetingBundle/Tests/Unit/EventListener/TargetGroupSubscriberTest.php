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
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\TargetGroupSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TargetGroupSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    public function setUp()
    {
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
    }

    public function testSetTargetGroupWithHeaderAndCookie()
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(GetResponseEvent::class);
        $request = new Request();

        $request->headers->set('X-Sulu-Target-Group', '1');
        $request->cookies->set('sulu-visitor-target-group', '2');

        $event->getRequest()->willReturn($request);

        $this->targetGroupStore->setTargetGroupId('1')->shouldBeCalled();
        $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();

        $targetGroupSubscriber->setTargetGroup($event->reveal());
    }

    /**
     * @dataProvider provideSetTargetGroupFromHeader
     */
    public function testSetTargetGroupFromHeader(
        $targetGroupHeader,
        $headerTargetGroup,
        $result
    ) {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            $targetGroupHeader,
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(GetResponseEvent::class);
        $request = new Request();

        if ($headerTargetGroup) {
            $request->headers->set($targetGroupHeader, $headerTargetGroup);
        }

        $event->getRequest()->willReturn($request);

        $this->targetGroupStore->setTargetGroupId($result)->shouldBeCalled();
        $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();

        $targetGroupSubscriber->setTargetGroup($event->reveal());
    }

    public function provideSetTargetGroupFromHeader()
    {
        return [
            ['X-Sulu-Target-Group', '1', '1'],
            ['X-Target-Group', '2', '2'],
            ['X-Sulu-Target-Group', '1', '1'],
        ];
    }

    /**
     * @dataProvider provideSetTargetGroupFromCookie
     */
    public function testSetTargetGroupFromCookie(
        $targetGroupCookie,
        $visitorSessionCookie,
        $cookieTargetGroup,
        $cookieVisitorSession,
        $evaluationResult,
        $result,
        $resultUpdate
    ) {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            $targetGroupCookie,
            $visitorSessionCookie
        );

        $event = $this->prophesize(GetResponseEvent::class);
        $request = new Request();

        if ($cookieTargetGroup) {
            $request->cookies->set($targetGroupCookie, $cookieTargetGroup);
        }

        if ($cookieVisitorSession) {
            $request->cookies->set($visitorSessionCookie, $cookieVisitorSession);
        }

        if ($evaluationResult) {
            $targetGroup = $this->prophesize(TargetGroupInterface::class);
            $targetGroup->getId()->willReturn($evaluationResult);
            $this->targetGroupRepository->find($cookieTargetGroup)->willReturn($targetGroup->reveal());
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_SESSION, $targetGroup->reveal())
                ->willReturn($targetGroup->reveal());
        }

        $event->getRequest()->willReturn($request);

        if ($resultUpdate) {
            $this->targetGroupStore->setTargetGroupId(Argument::any())->shouldNotBeCalled();
            $this->targetGroupStore->updateTargetGroupId($result)->shouldBeCalled();
        } else {
            $this->targetGroupStore->setTargetGroupId($result)->shouldBeCalled();
            $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();
        }

        $targetGroupSubscriber->setTargetGroup($event->reveal());
    }

    public function provideSetTargetGroupFromCookie()
    {
        return [
            ['sulu-visitor-target-group', 'visitor-session', '1', true, null, '1', false],
            ['target-group', 'session', '3', true, null, '3', false],
            ['sulu-visitor-target-group', 'visitor-session', '1', null, '2', '2', true],
            ['sulu-visitor-target-group', 'visitor-session', '1', true, '2', '1', false],
        ];
    }

    /**
     * @dataProvider provideSetTargetGroupFromEvaluation
     */
    public function testSetTargetGroupFromEvaluation($evaluatedTargetGroup, $result)
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(GetResponseEvent::class);
        $request = new Request();
        $event->getRequest()->willReturn($request);

        $this->targetGroupEvaluator->evaluate()->willReturn($evaluatedTargetGroup);

        $this->targetGroupStore->setTargetGroupId(Argument::any())->shouldNotBeCalled();
        $this->targetGroupStore->updateTargetGroupId($result)->shouldBeCalled();

        $targetGroupSubscriber->setTargetGroup($event->reveal());

        $this->assertCount(0, $request->headers->all());
    }

    public function provideSetTargetGroupFromEvaluation()
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

    public function testSetTargetGroupFromEvaluationOnTargetHitUrl()
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(GetResponseEvent::class);
        $request = Request::create('/_target_group');
        $event->getRequest()->willReturn($request);

        $this->targetGroupEvaluator->evaluate()->shouldNotBeCalled();

        $this->targetGroupStore->setTargetGroupId(Argument::any())->shouldNotBeCalled();
        $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();

        $targetGroupSubscriber->setTargetGroup($event->reveal());
    }

    /**
     * @dataProvider provideAddVaryHeader
     */
    public function testAddVaryHeader($targetGroupUrl, $requestUrl, $hasInfluencedContent, $header, $varyHeaders)
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $targetGroupUrl,
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            $header,
            'sulu-visitor-target-group',
            'visitor-session'
        );
        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $requestUrl]);
        $response = new Response();
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response);
        $this->targetGroupStore->hasInfluencedContent()->willReturn($hasInfluencedContent);

        $targetGroupSubscriber->addVaryHeader($event->reveal());

        $this->assertEquals($varyHeaders, $response->getVary());
    }

    public function provideAddVaryHeader()
    {
        return [
            ['/_target_group', '/test', true, 'X-Sulu-Target-Group-Hash', ['X-Sulu-Target-Group-Hash']],
            ['/_target_group', '/test', true, 'X-Sulu-Target-Group', ['X-Sulu-Target-Group']],
            ['/_target_group', '/test', false, 'X-Sulu-Target-Group', []],
            ['/_target_group', '/_target_group', true, 'X-Sulu-Target-Group-Hash', []],
            ['/_visitor', '/_visitor', true, 'X-Sulu-Target-Group-Hash', []],
        ];
    }

    /**
     * @dataProvider provideAddSetCookieHeader
     */
    public function testAddSetCookieHeader($targetGroupCookie, $visitorSession, $hasChanged, $url, $cookieValue)
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-URL',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            $targetGroupCookie,
            $visitorSession
        );

        $this->targetGroupStore->hasChangedTargetGroup()->willReturn($hasChanged);
        $this->targetGroupStore->getTargetGroupId(true)->willReturn($cookieValue);

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = Request::create($url);
        $event->getRequest()->willReturn($request);
        $response = new Response();
        $event->getResponse()->willReturn($response);

        $targetGroupSubscriber->addSetCookieHeader($event->reveal());

        if ($cookieValue) {
            $targetGroupResponseCookie = $response->headers->getCookies()[0];
            $this->assertEquals($targetGroupCookie, $targetGroupResponseCookie->getName());
            $this->assertEquals($cookieValue, $targetGroupResponseCookie->getValue());
            $visitorSessionResponseCookie = $response->headers->getCookies()[1];
            $this->assertEquals($visitorSession, $visitorSessionResponseCookie->getName());
            $this->assertEquals(0, $visitorSessionResponseCookie->getExpiresTime());
        } else {
            $this->assertCount(0, $response->headers->getCookies());
        }
    }

    public function provideAddSetCookieHeader()
    {
        return [
            ['sulu-visitor-target-group', 'visitor-session', false, '/_target_group_hit', null],
            ['target-group', 'session', true, '/_target_group_hit', 1],
            ['sulu-visitor-target-group', 'visitor-session', true, '/_tgh', 2],
            ['sulu-visitor-target-group', 'visitor-session', true, '/_target_group', null],
        ];
    }

    /**
     * @dataProvider provideAddTargetGroupHitScript
     */
    public function testAddTargetGroupHitScript(
        $targetGroupHitUrl,
        $forwardedUrlHeader,
        $forwardedRefererHeader,
        $forwardedUuidHeader,
        $uuid
    ) {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            $targetGroupHitUrl,
            $forwardedUrlHeader,
            $forwardedRefererHeader,
            $forwardedUuidHeader,
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
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
            'url' => $targetGroupHitUrl,
            'urlHeader' => $forwardedUrlHeader,
            'refererHeader' => $forwardedRefererHeader,
            'uuidHeader' => $forwardedUuidHeader,
            'uuid' => $uuid,
        ])->willReturn('<script></script>');

        $targetGroupSubscriber->addTargetGroupHitScript($event->reveal());

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }

    public function provideAddTargetGroupHitScript()
    {
        return [
            ['/_target_group_hit', 'X-Forwarded-URL', 'X-Fowarded-Referer', 'X-Forwarded-UUID', 'some-uuid'],
            ['/_target_group_hit', 'X-Forwarded-URL', 'X-Fowarded-Referer', 'X-Forwarded-UUID', null],
            ['/_group_hit', 'X-Other-URL', 'X-Other-Referer', 'X-Uuid', 'some-other-uuid'],
        ];
    }

    public function testAddTargetGroupHitScriptInPreview()
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            true,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $event->getRequest()->willReturn($request);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event->reveal());

        $this->assertEquals('', $response->getContent());
    }

    public function testAddTargetGroupHitScriptNonHtml()
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $event->getRequest()->willReturn($request);
        $response = new JsonResponse();
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event->reveal());

        $this->assertEquals('{}', $response->getContent());
    }

    public function testAddTargetGroupHitScriptHtmlUtf8()
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwared-Referer',
            'X-Fowarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $event->getRequest()->willReturn($request);
        $response = new Response('<body></body>');
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->willReturn('<script></script>');

        $targetGroupSubscriber->addTargetGroupHitScript($event->reveal());

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }

    public function testAddTargetGroupHitScriptNonGet()
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwared-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $event->getRequest()->willReturn($request);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $event->getResponse()->willReturn($response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event->reveal());

        $this->assertEquals('', $response->getContent());
    }
}
