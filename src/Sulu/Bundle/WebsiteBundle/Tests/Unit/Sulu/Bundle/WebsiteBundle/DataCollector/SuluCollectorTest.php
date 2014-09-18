<?php

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\DataCollector;

use Sulu\Bundle\WebsiteBundle\DataCollector\SuluCollector;
use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Component\HttpFoundation\Request;

class SuluCollectorTest extends ProphecyTestCase
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
        $this->structure = $this->prophesize('Sulu\Component\Content\Structure');

        $this->dataCollector = new SuluCollector($this->requestAnalyzer->reveal());
    }

    public function testCollectorNoComplexObjects()
    {
        $this->dataCollector->collect($this->request, $this->response->reveal());
    }

    public function testCollector()
    {
        $this->requestAnalyzer->getCurrentPortal()->willReturn($this->portal);
        $this->requestAnalyzer->getCurrentWebspace()->willReturn($this->webspace);
        $this->requestAnalyzer->getCurrentSegment()->willReturn($this->segment);
        $this->requestAnalyzer->getCurrentMatchType()->willReturn('match');
        $this->requestAnalyzer->getCurrentRedirect()->willReturn('red');
        $this->requestAnalyzer->getCurrentPortalUrl()->willReturn('/foo');

        $this->requestAnalyzer->getCurrentLocalization()->willReturn('de_de');
        $this->requestAnalyzer->getCurrentResourceLocator()->willReturn('/asd');
        $this->requestAnalyzer->getCurrentResourceLocatorPrefix()->willReturn('/asd/');
        $this->request->attributes->set('_route_params', array('structure' => $this->structure->reveal()));

        $this->dataCollector->collect($this->request, $this->response->reveal());

        $this->structure->toArray()->shouldHaveBeenCalled();
        $this->portal->toArray()->shouldHaveBeenCalled();
        $this->webspace->toArray()->shouldHaveBeenCalled();
        $this->segment->toArray()->shouldHaveBeenCalled();
    }
}
