<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class SystemListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SystemStoreInterface>
     */
    private $systemStore;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    public function setUp(): void
    {
        $this->systemStore = $this->prophesize(SystemStoreInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
    }

    public function testSetAdminSystem(): void
    {
        $systemListener = $this->createSystemListener('admin');
        $requestEvent = $this->prophesize(RequestEvent::class);
        $systemListener->onKernelRequest($requestEvent->reveal());

        $this->systemStore->setSystem('Sulu')->shouldBeCalled();
    }

    public function provideSetWebsiteSystem()
    {
        return [
            ['sulu-test'],
            ['sulu-blog'],
        ];
    }

    /**
     * @dataProvider provideSetWebsiteSystem
     */
    public function testSetWebsiteSystem(string $system): void
    {
        $systemListener = $this->createSystemListener('website');
        $requestEvent = $this->prophesize(RequestEvent::class);

        $webspace = new Webspace();
        $security = new Security();
        $security->setSystem($system);
        $webspace->setSecurity($security);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $systemListener->onKernelRequest($requestEvent->reveal());

        $this->systemStore->setSystem($system)->shouldBeCalled();
    }

    private function createSystemListener(string $context): SystemListener
    {
        return new SystemListener($this->systemStore->reveal(), $this->requestAnalyzer->reveal(), $context);
    }
}
