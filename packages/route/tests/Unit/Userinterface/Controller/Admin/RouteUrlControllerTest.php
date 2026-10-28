<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Userinterface\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Userinterface\Controller\Admin\RouteUrlController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(RouteUrlController::class)]
class RouteUrlControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    /**
     * @var ObjectProphecy<RouteGeneratorInterface>
     */
    private ObjectProphecy $routeGenerator;

    private RouteUrlController $controller;

    protected function setUp(): void
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->controller = new RouteUrlController($this->routeRepository->reveal(), $this->routeGenerator->reveal());
    }

    public function testRedirectsToUrlOfSavedRoute(): void
    {
        $this->routeRepository->findOneBy(['resourceKey' => 'articles', 'resourceId' => 'uuid-1', 'locale' => 'en'])
            ->willReturn(new Route('articles', 'uuid-1', 'en', '/news/hello'));
        $this->routeGenerator->generate('/news/hello', 'en', 'main', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://main.example/en/news/hello');

        $response = ($this->controller)(new Request([
            'resourceKey' => 'articles',
            'resourceId' => 'uuid-1',
            'locale' => 'en',
            'webspace' => 'main',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://main.example/en/news/hello', $response->getTargetUrl());
    }

    public function testThrowsNotFoundWithoutRoute(): void
    {
        $this->routeRepository->findOneBy(['resourceKey' => 'articles', 'resourceId' => 'uuid-1', 'locale' => 'en'])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        ($this->controller)(new Request(['resourceKey' => 'articles', 'resourceId' => 'uuid-1', 'locale' => 'en', 'webspace' => 'main']));
    }

    public function testThrowsNotFoundWithoutUrlForLocale(): void
    {
        $this->routeRepository->findOneBy(['resourceKey' => 'articles', 'resourceId' => 'uuid-1', 'locale' => 'de'])
            ->willReturn(new Route('articles', 'uuid-1', 'de', '/news/hello'));
        $this->routeGenerator->generate('/news/hello', 'de', 'blog', UrlGeneratorInterface::ABSOLUTE_URL)
            ->willThrow(new \RuntimeException('No url for locale'));

        $this->expectException(NotFoundHttpException::class);

        ($this->controller)(new Request(['resourceKey' => 'articles', 'resourceId' => 'uuid-1', 'locale' => 'de', 'webspace' => 'blog']));
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('provideInvalidQuery')]
    public function testThrowsBadRequestForInvalidQuery(array $query): void
    {
        $this->expectException(BadRequestHttpException::class);

        ($this->controller)(new Request($query));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideInvalidQuery(): iterable
    {
        $query = ['resourceKey' => 'articles', 'resourceId' => 'uuid-1', 'locale' => 'en', 'webspace' => 'main'];

        foreach (\array_keys($query) as $missing) {
            yield 'missing ' . $missing => [\array_diff_key($query, [$missing => true])];
        }
    }
}
