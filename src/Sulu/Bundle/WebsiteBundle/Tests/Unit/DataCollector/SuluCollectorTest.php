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
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
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
        $this->attributes = $this->prophesize(ParameterBag::class);
        $this->response = $this->prophesize(Response::class);

        $this->suluCollector = new SuluCollector('/admin/view-redirect');
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

    public function testCollectorWithPage(): void
    {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('test-webspace');

        $portal = $this->prophesize(Portal::class);
        $segment = $this->prophesize(Segment::class);

        $page = $this->prophesize(Page::class);
        $page->getId()->willReturn('456');
        $page->getCreated()->willReturn(new \DateTimeImmutable());
        $page->getChanged()->willReturn(new \DateTimeImmutable());
        $page->getCreator()->willReturn(null);
        $page->getChanger()->willReturn(null);

        $pageDimensionContent = new PageDimensionContent($page->reveal());

        (new \ReflectionProperty($pageDimensionContent, 'id'))->setValue($pageDimensionContent, 1);
        $pageDimensionContent->setTemplateKey('page.html.twig');
        $pageDimensionContent->setLocale('de');

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
        $request->attributes->set('object', $pageDimensionContent);

        $webspace->toArray()->shouldBeCalled();
        $portal->toArray()->shouldBeCalled();
        $portal->getEnvironment('dev')->shouldBeCalled()->willReturn([]);
        $segment->toArray()->shouldBeCalled();

        $this->suluCollector->collect($request, $this->response->reveal());

        $data = $this->suluCollector->data('structure');
        unset($data['created'], $data['changed']);

        $this->assertEquals([
            'id' => '456',
            'class' => ($page->reveal())::class,
            'dimensionClass' => PageDimensionContent::class,
            'nodeState' => 'draft',
            'locale' => 'de',
            'availableLocales' => null,
            'ghostLocale' => null,
            'template' => 'page.html.twig',
            'published' => null,
            'navContexts' => [],
            'shadowLocales' => null,
            'adminUrl' => '/admin/view-redirect?resourceKey=pages&view=detail&locale=de&id=456&webspace=test-webspace',
        ], $data);
    }
}
