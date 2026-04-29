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

use FOS\RestBundle\View\ViewHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Controller\SmartContentItemController;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SmartContentItemControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SmartContentProviderInterface>
     */
    private ObjectProphecy $provider;

    /**
     * @var ObjectProphecy<ViewHandlerInterface>
     */
    private ObjectProphecy $viewHandler;

    private SmartContentItemController $controller;

    protected function setUp(): void
    {
        $this->provider = $this->prophesize(SmartContentProviderInterface::class);
        $this->provider->findFlatBy(Argument::cetera())->willReturn([]);

        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->viewHandler->handle(Argument::cetera())->willReturn(new JsonResponse());

        /** @var ServiceLocator<SmartContentProviderInterface> $locator */
        $locator = new ServiceLocator([
            'articles' => fn () => $this->provider->reveal(),
        ]);

        $this->controller = new SmartContentItemController(
            $locator,
            $this->viewHandler->reveal(),
        );
    }

    public function testTypesFromQueryString(): void
    {
        $request = Request::create('/api/items', 'GET', [
            'provider' => 'articles', 'locale' => 'en', 'types' => 'news,press',
        ]);

        $this->controller->getItemsAction($request);

        $this->provider->findFlatBy(
            Argument::that(fn (array $f) => ['news', 'press'] === $f['types']),
            Argument::cetera(),
        )->shouldHaveBeenCalled();
    }

    public function testIgnoreWebspacesNullsWebspaceKey(): void
    {
        $params = \json_encode(['ignoreWebspaces' => ['type' => 'string', 'value' => 'true']]);
        $request = Request::create('/api/items', 'GET', [
            'provider' => 'articles', 'locale' => 'en', 'webspace' => 'sulu-io', 'params' => $params,
        ]);

        $this->controller->getItemsAction($request);

        $this->provider->findFlatBy(
            Argument::that(fn (array $f) => null === $f['webspaceKey']),
            Argument::cetera(),
        )->shouldHaveBeenCalled();
    }

    public function testWebspaceKeyPreservedWithoutIgnoreWebspaces(): void
    {
        $request = Request::create('/api/items', 'GET', [
            'provider' => 'articles', 'locale' => 'en', 'webspace' => 'sulu-io',
        ]);

        $this->controller->getItemsAction($request);

        $this->provider->findFlatBy(
            Argument::that(fn (array $f) => 'sulu-io' === $f['webspaceKey']),
            Argument::cetera(),
        )->shouldHaveBeenCalled();
    }

    public function testParamsNormalizedToPlainValues(): void
    {
        $params = \json_encode([
            'groups' => ['type' => 'string', 'value' => 'blog,rblog'],
            'template' => ['type' => 'string', 'value' => 'blog_article'],
        ]);
        $request = Request::create('/api/items', 'GET', [
            'provider' => 'articles', 'locale' => 'en', 'params' => $params,
        ]);

        $this->controller->getItemsAction($request);

        $this->provider->findFlatBy(
            Argument::any(),
            Argument::any(),
            Argument::that(function(array $normalizedParams) {
                return 'blog,rblog' === $normalizedParams['groups']
                    && 'blog_article' === $normalizedParams['template'];
            }),
        )->shouldHaveBeenCalled();
    }

    public function testEmptyTypesWhenNotProvided(): void
    {
        $request = Request::create('/api/items', 'GET', [
            'provider' => 'articles', 'locale' => 'en',
        ]);

        $this->controller->getItemsAction($request);

        $this->provider->findFlatBy(
            Argument::that(fn (array $f) => [] === $f['types']),
            Argument::cetera(),
        )->shouldHaveBeenCalled();
    }
}
