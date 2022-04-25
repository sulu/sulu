<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Functional\EventListener;

use App\Kernel;
use DeviceDetector\DeviceDetector;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\DeviceDetectorSubscriber;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class DeviceDetectorSubscriberTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var DeviceDetector
     */
    private $deviceDetector;

    /**
     * @var DeviceDetectorSubscriber
     */
    private $deviceDetectorSubscriber;

    protected function setUp(): void
    {
        $this->deviceDetector = new DeviceDetector();
        $this->deviceDetectorSubscriber = new DeviceDetectorSubscriber($this->deviceDetector);
    }

    public function testSetUserAgent(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $request = new Request();
        $request->headers->add(['User-Agent' => 'Test-Agent']);

        $requestEvent = new RequestEvent($kernel->reveal(), $request, null);

        $this->deviceDetectorSubscriber->setUserAgent($requestEvent);

        $this->assertSame('Test-Agent', $this->deviceDetector->getUserAgent());
    }

    public function testSetUserAgentNull(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $request = new Request();
        $request->headers->add([]);

        $requestEvent = new RequestEvent($kernel->reveal(), $request, null);

        $this->deviceDetectorSubscriber->setUserAgent($requestEvent);

        $this->assertSame('', $this->deviceDetector->getUserAgent());
    }
}
