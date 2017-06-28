<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Rule;

use Sulu\Bundle\AudienceTargetingBundle\Rule\ReferrerRule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class ReferrerRuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function setUp()
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    /**
     * @dataProvider provideEvaluationData
     */
    public function testEvaluate($referrerHeader, $referrer, $options, $result)
    {
        $referrerRule = new ReferrerRule($this->requestStack->reveal(), $this->translator->reveal(), $referrerHeader);

        $headers = [];
        if (!$referrerHeader) {
            $headers = ['HTTP_REFERER' => $referrer];
        }

        $request = new Request([], [], [], [], [], $headers);
        if ($referrerHeader) {
            $request->headers->add([$referrerHeader => $referrer]);
        }

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertEquals($result, $referrerRule->evaluate($options));
    }

    public function provideEvaluationData()
    {
        return [
            [null, 'https://www.google.com/test', ['referrer' => 'https://www.google.com/*'], true],
            [null, 'https://www.google.com', ['referrer' => 'www.google.*'], false],
            [null, 'https://www.google.com', ['referrer' => '*.google.*'], true],
            [null, 'https://www.google.com?q=test', ['referrer' => '*.google.*q=test'], true],
            [null, 'https://www.google.com?q=test', ['referrer' => '*.google.*'], true],
            [null, 'https://www.google.com', ['referrer' => '*.google.*q=test'], false],
            [null, '', ['referrer' => '*.google.*q=test'], false],
            [null, null, ['referrer' => '*.google.*q=test'], false],
            [null, 'https://www.google.com', ['referrer' => ''], false],
            ['X-Forwarded-Referer', 'https://www.google.com', ['referrer' => 'www.google.*'], false],
            ['X-Forwarded-Referer', 'https://www.google.com/test', ['referrer' => 'https://www.google.com/*'], true],
        ];
    }

    public function testEvaluateWithBothHeaders()
    {
        $referrerRule = new ReferrerRule($this->requestStack->reveal(), $this->translator->reveal(), 'X-Forwarded-Referrer');

        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => '/wrong/referer']);
        $request->headers->add(['X-Forwarded-Referrer' => '/right/referer']);

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertTrue($referrerRule->evaluate(['referrer' => '/right/referer']));
        $this->assertFalse($referrerRule->evaluate(['referrer' => '/wrong/referer']));
    }

    public function testEvaluateWithoutConfiguredHeader()
    {
        $referrerRule = new ReferrerRule($this->requestStack->reveal(), $this->translator->reveal(), 'X-Forwarded-Referrer');

        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => '/right/referer']);

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertTrue($referrerRule->evaluate(['referrer' => '/right/referer']));
    }
}
