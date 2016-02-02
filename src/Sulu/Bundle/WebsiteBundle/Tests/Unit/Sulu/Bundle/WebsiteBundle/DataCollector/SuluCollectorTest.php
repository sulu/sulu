<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\DataCollector;

use Sulu\Bundle\WebsiteBundle\DataCollector\SuluCollector;
use Symfony\Component\HttpFoundation\Request;

class SuluCollectorTest extends \PHPUnit_Framework_TestCase
{
    protected $requestAnalyzer;
    protected $request;
    protected $response;

    public function setUp()
    {
        parent::setUp();
        $this->requestAnalyzer = $this->prophesize('Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface');
        $this->request = new Request();
        $this->response = $this->prophesize('Symfony\Component\HttpFoundation\Response');
        $this->portal = $this->prophesize('Sulu\Component\Webspace\Portal');
        $this->webspace = $this->prophesize('Sulu\Component\Webspace\Webspace');
        $this->segment = $this->prophesize('Sulu\Component\Webspace\Segment');
        $this->structure = $this->prophesize('Sulu\Component\Content\Compat\Structure\PageBridge');

        $this->dataCollector = new SuluCollector($this->requestAnalyzer->reveal());
    }

    public function testCollectorNoComplexObjects()
    {
        $this->dataCollector->collect($this->request, $this->response->reveal());
    }

    public function testCollector()
    {
        $this->requestAnalyzer->getPortal()->willReturn($this->portal);
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace);
        $this->requestAnalyzer->getSegment()->willReturn($this->segment);
        $this->requestAnalyzer->getMatchType()->willReturn('match');
        $this->requestAnalyzer->getRedirect()->willReturn('red');
        $this->requestAnalyzer->getPortalUrl()->willReturn('/foo');

        $this->requestAnalyzer->getCurrentLocalization()->willReturn('de_de');
        $this->requestAnalyzer->getResourceLocator()->willReturn('/asd');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/asd/');
        $this->request->attributes->set('_route_params', ['structure' => $this->structure->reveal()]);

        $this->dataCollector->collect($this->request, $this->response->reveal());

        $this->portal->toArray()->shouldHaveBeenCalled();
        $this->webspace->toArray()->shouldHaveBeenCalled();
        $this->segment->toArray()->shouldHaveBeenCalled();
    }
}
