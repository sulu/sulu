<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Controller\WebsiteEditController;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebsiteEditControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ResourceViewUrlGeneratorInterface>
     */
    private ObjectProphecy $resourceViewUrlGenerator;

    private WebsiteEditController $controller;

    protected function setUp(): void
    {
        $this->resourceViewUrlGenerator = $this->prophesize(ResourceViewUrlGeneratorInterface::class);

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('sulu_admin')->willReturn('/admin/');

        $this->controller = new WebsiteEditController(
            $this->resourceViewUrlGenerator->reveal(),
            $urlGenerator->reveal(),
        );
    }

    public function testRedirectsToDetailView(): void
    {
        $this->resourceViewUrlGenerator->generate(
            'pages',
            'detail',
            ['id' => '123-abc', 'locale' => 'de', 'webspace' => 'example'],
        )->willReturn('/admin/#/webspaces/example/pages/de/123-abc/details');

        $response = $this->controller->redirectAction(Request::create(
            '/admin/website-edit',
            'GET',
            ['resourceKey' => 'pages', 'resourceId' => '123-abc', 'locale' => 'de', 'webspace' => 'example'],
        ));

        $this->assertSame('/admin/#/webspaces/example/pages/de/123-abc/details', $response->getTargetUrl());
    }

    public function testOmitsEmptyParameters(): void
    {
        $this->resourceViewUrlGenerator->generate('articles', 'detail', ['id' => '456', 'locale' => 'en'])
            ->willReturn('/admin/#/articles/en/456/details');

        $response = $this->controller->redirectAction(Request::create(
            '/admin/website-edit',
            'GET',
            ['resourceKey' => 'articles', 'resourceId' => '456', 'locale' => 'en', 'webspace' => ''],
        ));

        $this->assertSame('/admin/#/articles/en/456/details', $response->getTargetUrl());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideIncompleteQueries(): iterable
    {
        yield 'no parameters' => [[]];
        yield 'no resource id' => [['resourceKey' => 'pages']];
        yield 'no resource key' => [['resourceId' => '123']];
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('provideIncompleteQueries')]
    public function testRedirectsToStartPageWithoutTarget(array $query): void
    {
        $this->resourceViewUrlGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $response = $this->controller->redirectAction(Request::create('/admin/website-edit', 'GET', $query));

        $this->assertSame('/admin/', $response->getTargetUrl());
    }

    /**
     * @return iterable<string, array{\Exception}>
     */
    public static function provideGeneratorExceptions(): iterable
    {
        yield 'unknown resource' => [new ResourceViewNotFoundException('unknown', 'detail')];
        yield 'no access to view' => [new ViewNotFoundException('sulu_page.page_edit_form')];
        yield 'missing view parameter' => [new ViewParameterNotFoundException('webspace', 'sulu_page.page_edit_form')];
    }

    #[DataProvider('provideGeneratorExceptions')]
    public function testRedirectsToStartPageWhenViewCannotBeGenerated(\Exception $exception): void
    {
        $this->resourceViewUrlGenerator->generate('pages', 'detail', Argument::any())->willThrow($exception);

        $response = $this->controller->redirectAction(Request::create(
            '/admin/website-edit',
            'GET',
            ['resourceKey' => 'pages', 'resourceId' => '123'],
        ));

        $this->assertSame('/admin/', $response->getTargetUrl());
    }
}
