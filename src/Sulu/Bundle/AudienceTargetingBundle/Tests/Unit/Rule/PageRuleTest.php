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

use Sulu\Bundle\AudienceTargetingBundle\Rule\PageRule;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class PageRuleTest extends \PHPUnit_Framework_TestCase
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
     * @dataProvider provideEvaluate
     */
    public function testEvaluate($requestUuid, $uuidHeader, $uuidValue, $ruleUuid, $result)
    {
        $pageRule = new PageRule($this->requestStack->reveal(), $this->translator->reveal(), $uuidHeader);

        $requestAttributes = [];
        if ($requestUuid) {
            $structureBridge = $this->prophesize(StructureBridge::class);
            $structureBridge->getUuid()->willReturn($requestUuid);
            $requestAttributes = ['structure' => $structureBridge->reveal()];
        }

        $request = new Request([], [], $requestAttributes);

        if ($uuidValue) {
            $request->headers->set($uuidHeader, $uuidValue);
        }

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertEquals($result, $pageRule->evaluate(['page' => $ruleUuid]));
    }

    public function provideEvaluate()
    {
        return [
            ['some-uuid', 'X-Forwarded-UUID', null, 'some-uuid', true],
            ['some-uuid', 'X-Forwarded-UUID', null, 'some-other-uuid', false],
            [null, 'X-Forwarded-UUID', 'some-uuid', 'some-uuid', true],
            [null, 'X-UUID', 'some-uuid', 'some-uuid', true],
            [null, 'X-Forwarded-UUID', null, 'some-uuid', false],
            [null, 'X-UUID', null, 'some-uuid', false],
            ['some-uuid', 'X-Forwarded-UUID', 'some-other-uuid', 'some-uuid', false],
        ];
    }

    public function testEvaluateWithoutStructure()
    {
        $pageRule = new PageRule($this->requestStack->reveal(), $this->translator->reveal(), 'X-Forwarded-UUID');

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertEquals(false, $pageRule->evaluate(['page' => 'some-uuid']));
    }
}
