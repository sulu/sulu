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

use DeviceDetector\DeviceDetector;
use Sulu\Bundle\AudienceTargetingBundle\Rule\DeviceTypeRule;
use Symfony\Component\Translation\TranslatorInterface;

class DeviceTypeRuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeviceDetector
     */
    private $deviceDetector;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var DeviceTypeRule
     */
    private $deviceTypeRule;

    public function setUp()
    {
        $this->deviceDetector = $this->prophesize(DeviceDetector::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->deviceTypeRule = new DeviceTypeRule($this->deviceDetector->reveal(), $this->translator->reveal());
    }

    /**
     * @dataProvider provideEvaluate
     */
    public function testEvaluate($deviceType, $options, $result)
    {
        $this->deviceDetector->isDesktop()->willReturn(DeviceTypeRule::DESKTOP === $deviceType);
        $this->deviceDetector->isTablet()->willReturn(DeviceTypeRule::TABLET === $deviceType);
        $this->deviceDetector->isSmartphone()->willReturn(DeviceTypeRule::SMARTPHONE === $deviceType);

        $this->assertSame($result, $this->deviceTypeRule->evaluate($options));
    }

    public function provideEvaluate()
    {
        return [
            [null, ['device_type' => DeviceTypeRule::DESKTOP], false],
            [DeviceTypeRule::DESKTOP, ['device_type' => DeviceTypeRule::DESKTOP], true],
            [DeviceTypeRule::TABLET, ['device_type' => DeviceTypeRule::TABLET], true],
            [DeviceTypeRule::SMARTPHONE, ['device_type' => DeviceTypeRule::SMARTPHONE], true],
            [DeviceTypeRule::DESKTOP, ['device_type' => DeviceTypeRule::SMARTPHONE], false],
            [DeviceTypeRule::DESKTOP, [], false],
        ];
    }
}
