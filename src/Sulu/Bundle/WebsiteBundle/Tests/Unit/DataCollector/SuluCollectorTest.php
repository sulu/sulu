<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\DataCollector;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\DataCollector\SuluCollector;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SuluCollectorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ParameterBag>
     */
    protected ObjectProphecy $attributes;

    /**
     * @var ObjectProphecy<Response>
     */
    protected ObjectProphecy $response;

    private SuluCollector $suluCollector;

    public function setUp(): void
    {
        $this->response = $this->prophesize(Response::class);

        $this->suluCollector = new SuluCollector();
    }

    public function testCollectorNoComplexObjects(): void
    {
        $request = Request::create('/', parameters: []);

        $this->suluCollector->collect($request, $this->response->reveal());
        $this->assertEquals(null, $this->suluCollector->data('structure'));
    }

    public function testCollectorWithAnyPage(): void
    {
        $webspace = $this->prophesize(Webspace::class);
        $portal = $this->prophesize(Portal::class);
        $segment = $this->prophesize(Segment::class);

        $request = Request::create('/');
        $request->attributes->set('_sulu', new RequestAttributes([
            'webspace' => $webspace->reveal(),
            'portal' => $portal->reveal(),
            'segment' => $segment->reveal(),
            'matchType' => 'match',
            'redirect' => 'red',
            'portalUrl' => '/foo',
            'localization' => 'de_de',
            'resourceLocator' => '/asd',
            'resourceLocatorPrefix' => '/asd/',
        ]));

        $webspace->toArray()->shouldBeCalled();
        $portal->toArray()->shouldBeCalled();
        $portal->getEnvironment('dev')->shouldBeCalled()->willReturn([]);
        $segment->toArray()->shouldBeCalled();

        $this->suluCollector->collect($request, $this->response->reveal());
        $this->assertEquals(null, $this->suluCollector->data('structure'));
    }

    public function testCollectorWithDocument(): void
    {
        $webspace = $this->prophesize(Webspace::class);
        $portal = $this->prophesize(Portal::class);
        $segment = $this->prophesize(Segment::class);

        $page = $this->prophesize(DimensionContentInterface::class);
        $resource = $this->prophesize(ContentRichEntityInterface::class);
        $resource->getId()->willReturn('123');

        $page->getResource()->willReturn($resource->reveal());
        $page->getStage()->willReturn('published');
        $page->getLocale()->willReturn('de');
        $page->getAvailableLocales()->willReturn(['de', 'en']);
        $page->getGhostLocale()->willReturn(null);

        $request = Request::create('/');
        $request->attributes->set('_sulu', new RequestAttributes([
            'webspace' => $webspace->reveal(),
            'portal' => $portal->reveal(),
            'segment' => $segment->reveal(),
            'matchType' => 'match',
            'redirect' => 'red',
            'portalUrl' => '/foo',
            'localization' => 'de_de',
            'resourceLocator' => '/asd',
            'resourceLocatorPrefix' => '/asd/',
        ]));
        $request->attributes->set('object', $page->reveal());

        $webspace->toArray()->shouldBeCalled();
        $portal->toArray()->shouldBeCalled();
        $portal->getEnvironment('dev')->shouldBeCalled()->willReturn([]);
        $segment->toArray()->shouldBeCalled();

        $this->suluCollector->collect($request, $this->response->reveal());
        $this->assertEquals([
            'id' => '123',
            'class' => ($resource->reveal())::class,
            'dimensionClass' => ($page->reveal())::class,
            'nodeState' => 'published',
            'locale' => 'de',
            'availableLocales' => ['de', 'en'],
            'ghostLocale' => null,
        ], $this->suluCollector->data('structure'));
    }
}
