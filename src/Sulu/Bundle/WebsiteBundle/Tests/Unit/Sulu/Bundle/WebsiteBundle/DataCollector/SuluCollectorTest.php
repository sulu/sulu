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

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\DataCollector\SuluCollector;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SuluCollectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ParameterBag
     */
    protected $attributes;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var SuluCollector
     */
    private $suluCollector;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->attributes = $this->prophesize(ParameterBag::class);
        $this->request->reveal()->attributes = $this->attributes->reveal();
        $this->response = $this->prophesize(Response::class);

        $this->suluCollector = new SuluCollector();
    }

    public function testCollectorNoComplexObjects()
    {
        $this->attributes->has('_sulu')->willReturn(false)->shouldBeCalled();
        $this->attributes->get(Argument::any())->shouldNotBeCalled();
        $this->suluCollector->collect($this->request->reveal(), $this->response->reveal());
    }

    public function testCollector()
    {
        $structure = $this->prophesize(PageBridge::class);

        $webspace = $this->prophesize(Webspace::class);
        $portal = $this->prophesize(Portal::class);
        $segment = $this->prophesize(Segment::class);

        $this->attributes->has('_sulu')->willReturn(true)->shouldBeCalled();
        $this->attributes->get('_sulu')->willReturn(new RequestAttributes(
            [
                'webspace' => $webspace->reveal(),
                'portal' => $portal->reveal(),
                'segment' => $segment->reveal(),
                'matchType' => 'match',
                'redirect' => 'red',
                'portalUrl' => '/foo',
                'localization' => 'de_de',
                'resourceLocator' => '/asd',
                'resourceLocatorPrefix' => '/asd/',
            ]
        ))->shouldBeCalled();

        $this->attributes->has('_route_params')->willReturn(true)->shouldBeCalled();
        $this->attributes->get('_route_params')->willReturn(['structure' => $structure->reveal()])->shouldBeCalled();

        $webspace->toArray()->shouldBeCalled();
        $portal->toArray()->shouldBeCalled();
        $segment->toArray()->shouldBeCalled();

        $this->suluCollector->collect($this->request->reveal(), $this->response->reveal());
    }
}
