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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentLocalizationsResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentLocalizationsResolver\RouteLocalizationsResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Route\Exception\WebspaceUrlNotFoundException;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class RouteLocalizationsResolverTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    /**
     * @var ObjectProphecy<RouteGeneratorInterface>
     */
    private ObjectProphecy $routeGenerator;

    private RouteLocalizationsResolver $resolver;

    protected function setUp(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $webspace->addLocalization(new Localization('en'));
        $webspace->addLocalization(new Localization('de', 'at'));
        $webspace->addLocalization(new Localization('fr'));

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $webspaceManager->getWebspaceCollection()->willReturn(new WebspaceCollection(['sulu-io' => $webspace]));

        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);

        $this->resolver = new RouteLocalizationsResolver(
            $webspaceManager->reveal(),
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );
    }

    public function testContentWithoutRouteHasNoLocalizations(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->assertSame([], $this->resolver->resolve($dimensionContent->reveal(), 'sulu-io'));
    }

    public function testRoutesReplaceTheStartPageOfTheirLocale(): void
    {
        $this->routeGenerator->generate('/', 'en', 'sulu-io')->willReturn('/en');
        $this->routeGenerator->generate('/', 'de_at', 'sulu-io')->willReturn('/de-at');
        $this->routeGenerator->generate('/', 'fr', 'sulu-io')->willReturn('/fr');
        $this->routeGenerator->generate('/my-example', 'en', 'sulu-io')->willReturn('/en/my-example');
        $this->routeGenerator->generate('/mein-beispiel', 'de_at', 'sulu-io')->willReturn('/de-at/mein-beispiel');

        $this->routeRepository->findBy([
            'resourceKey' => 'examples',
            'resourceId' => '1',
            'locales' => ['en', 'de_at'],
        ])->willReturn([
            new Route('examples', '1', 'en', '/my-example'),
            new Route('examples', '1', 'de_at', '/mein-beispiel'),
        ]);

        $this->assertSame([
            'en' => ['url' => '/en/my-example', 'locale' => 'en', 'alternate' => true],
            'de_at' => ['url' => '/de-at/mein-beispiel', 'locale' => 'de_at', 'alternate' => true],
            'fr' => ['url' => '/fr', 'locale' => 'fr', 'alternate' => false],
        ], $this->resolver->resolve($this->createDimensionContent(['en', 'de_at']), 'sulu-io'));
    }

    public function testLocalesWithoutWebspaceUrlAreSkipped(): void
    {
        $this->routeGenerator->generate('/', 'en', 'sulu-io')->willReturn('/en');
        $this->routeGenerator->generate('/', 'de_at', 'sulu-io')->willThrow(new WebspaceUrlNotFoundException('/', 'de_at', 'sulu-io'));
        $this->routeGenerator->generate('/', 'fr', 'sulu-io')->willReturn('/fr');
        $this->routeGenerator->generate('/my-example', 'en', 'sulu-io')->willReturn('/en/my-example');
        $this->routeGenerator->generate('/mon-exemple', 'fr', 'sulu-io')->willThrow(new WebspaceUrlNotFoundException('/mon-exemple', 'fr', 'sulu-io'));

        $this->routeRepository->findBy([
            'resourceKey' => 'examples',
            'resourceId' => '1',
            'locales' => ['en', 'fr'],
        ])->willReturn([
            new Route('examples', '1', 'en', '/my-example'),
            new Route('examples', '1', 'fr', '/mon-exemple'),
        ]);

        $this->assertSame([
            'en' => ['url' => '/en/my-example', 'locale' => 'en', 'alternate' => true],
            'fr' => ['url' => '/fr', 'locale' => 'fr', 'alternate' => false],
        ], $this->resolver->resolve($this->createDimensionContent(['en', 'fr']), 'sulu-io'));
    }

    public function testUnknownWebspaceKeepsOnlyTheRoutes(): void
    {
        $this->routeGenerator->generate('/my-example', 'en', 'other')->willReturn('/en/my-example');

        $this->routeRepository->findBy([
            'resourceKey' => 'examples',
            'resourceId' => '1',
            'locales' => ['en'],
        ])->willReturn([new Route('examples', '1', 'en', '/my-example')]);

        $this->assertSame([
            'en' => ['url' => '/en/my-example', 'locale' => 'en', 'alternate' => true],
        ], $this->resolver->resolve($this->createDimensionContent(['en']), 'other'));
    }

    /**
     * @param string[] $availableLocales
     */
    private function createDimensionContent(array $availableLocales): ExampleDimensionContent
    {
        $example = new Example();
        self::setPrivateProperty($example, 'id', 1);

        $dimensionContent = new ExampleDimensionContent($example);
        foreach ($availableLocales as $availableLocale) {
            $dimensionContent->addAvailableLocale($availableLocale);
        }

        return $dimensionContent;
    }
}
