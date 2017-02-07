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
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SuluCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

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
        $this->request = new Request();
        $this->response = $this->prophesize(Response::class);

        $this->suluCollector = new SuluCollector();
    }

    public function testCollectorNoComplexObjects()
    {
        $this->suluCollector->collect($this->request, $this->response->reveal());
    }

    public function testCollector()
    {
        $structure = $this->prophesize(PageBridge::class);

        $webspace = $this->prophesize(Webspace::class);
        $portal = $this->prophesize(Portal::class);
        $segment = $this->prophesize(Segment::class);

        $this->request->attributes->set('_sulu', new RequestAttributes(
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
        ));

        $this->request->attributes->set('_route_params', ['structure' => $structure->reveal()]);

        $webspace->toArray()->shouldBeCalled();
        $portal->toArray()->shouldBeCalled();
        $segment->toArray()->shouldBeCalled();

        $this->suluCollector->collect($this->request, $this->response->reveal());
    }
}
