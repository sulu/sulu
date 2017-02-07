<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use Sulu\Component\Webspace\Analyzer\Attributes\ParameterRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;

class ParameterRequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ParameterRequestProcessor
     */
    private $parameterRequestProcessor;

    public function setUp()
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->parameterRequestProcessor = new ParameterRequestProcessor(
            $this->webspaceManager->reveal(),
            'dev'
        );
    }

    public function testProcess()
    {
        $request = new Request(['_portal' => 'sulu_io', '_locale' => 'de']);

        $portalInformation = new PortalInformation(1);

        $this->webspaceManager->findPortalInformationsByPortalKeyAndLocale('sulu_io', 'de', 'dev')
            ->willReturn([$portalInformation]);

        $requestAttributes = $this->parameterRequestProcessor->process($request, new RequestAttributes());

        $this->assertEquals($portalInformation, $requestAttributes->getAttribute('portalInformation'));
    }

    public function testProcessWithoutLocale()
    {
        $request = new Request(['_portal' => 'sulu_io']);

        $this->assertEquals(
            new RequestAttributes(),
            $this->parameterRequestProcessor->process($request, new RequestAttributes())
        );
    }

    public function testProcessWithoutPortal()
    {
        $request = new Request(['_locale' => 'sulu_io']);

        $this->assertEquals(
            new RequestAttributes(),
            $this->parameterRequestProcessor->process($request, new RequestAttributes())
        );
    }

    public function testValidate()
    {
        $this->assertTrue($this->parameterRequestProcessor->validate(new RequestAttributes()));
    }
}
