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

    /**
     * @var ReferrerRule
     */
    private $referrerRule;

    public function setUp()
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->referrerRule = new ReferrerRule($this->requestStack->reveal(), $this->translator->reveal());
    }

    /**
     * @dataProvider provideEvaluationData
     */
    public function testEvaluate($referrer, $options, $result)
    {
        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => $referrer]);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertEquals($result, $this->referrerRule->evaluate($options));
    }

    public function provideEvaluationData()
    {
        return [
            ['https://www.google.com/test', ['referrer' => 'https://www.google.com/*'], true],
            ['https://www.google.com', ['referrer' => 'www.google.*'], false],
            ['https://www.google.com', ['referrer' => '*.google.*'], true],
            ['https://www.google.com?q=test', ['referrer' => '*.google.*q=test'], true],
            ['https://www.google.com?q=test', ['referrer' => '*.google.*'], true],
            ['https://www.google.com', ['referrer' => '*.google.*q=test'], false],
            ['', ['referrer' => '*.google.*q=test'], false],
            [null, ['referrer' => '*.google.*q=test'], false],
            ['https://www.google.com', ['referrer' => ''], false],
        ];
    }
}
