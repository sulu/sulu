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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\ContentResolver\Resolver\SettingsResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\Reference;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @phpstan-import-type SettingsData from SettingsResolver
 */
class SettingsResolverTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    private SettingsResolver $resolver;

    /** @var ObjectProphecy<RouteGeneratorInterface> */
    private ObjectProphecy $routeGenerator;

    /** @var ObjectProphecy<RouteRepositoryInterface> */
    private ObjectProphecy $routeRepository;

    /** @var ObjectProphecy<RequestContext> */
    private ObjectProphecy $requestContext;

    protected function setUp(): void
    {
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->requestContext = $this->prophesize(RequestContext::class);

        // Mock RequestContext methods
        $this->requestContext->getParameter(Argument::any())->willReturn(null);
        $this->requestContext->setParameter(Argument::any(), Argument::any())->willReturn($this->requestContext->reveal());

        $this->resolver = new SettingsResolver(
            $this->routeGenerator->reveal(),
            $this->routeRepository->reveal(),
            $this->requestContext->reveal(),
        );
    }

    public function testResolveAvailableLocales(): void
    {
        $example = new Example();
        $this->setPrivateProperty($example, 'id', 1);
        $exampleDimensionUnlocalized = new ExampleDimensionContent($example);
        $example->addDimensionContent($exampleDimensionUnlocalized);
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->addAvailableLocale('de');
        $exampleDimension->addAvailableLocale('en');
        $example->addDimensionContent($exampleDimension);

        $this->routeRepository->findBy(Argument::cetera())
            ->willReturn([])
            ->shouldBeCalled();

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame(['de', 'en'], $content['availableLocales'] ?? null);
    }

    public function testResolveNoAvailableLocales(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame([], $content['availableLocales'] ?? null);
    }

    public function testResolveLocalizationsNoUrl(): void
    {
        $example = new Example();
        $this->setPrivateProperty($example, 'id', 1);
        $exampleDimensionUnlocalized = new ExampleDimensionContent($example);
        $example->addDimensionContent($exampleDimensionUnlocalized);
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->addAvailableLocale('de');
        $exampleDimension->addAvailableLocale('en');
        $example->addDimensionContent($exampleDimension);

        $this->routeRepository->findBy(Argument::cetera())
            ->willReturn([])
            ->shouldBeCalled();

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame([], $content['localizations'] ?? null);
    }

    public function testResolveLocalizations(): void
    {
        $example = new Example();
        $this->setPrivateProperty($example, 'id', 1);
        $exampleDimensionUnlocalized = new ExampleDimensionContent($example);
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setLocale('de');
        $exampleDimension->setTemplateData(['url' => '/test']);
        $exampleDimension->setMainWebspace('sulu_io');
        $exampleDimension->addAvailableLocale('de');
        $exampleDimension->addAvailableLocale('en');
        $example->addDimensionContent($exampleDimensionUnlocalized);
        $example->addDimensionContent($exampleDimension);

        $routeEn = new Route('example', '1', 'en', '/example');
        $this->setPrivateProperty($routeEn, 'id', 10);
        $routeDe = new Route('example', '1', 'de', '/beispiel');
        $this->setPrivateProperty($routeEn, 'id', 11);
        $routeFr = new Route('example', '1', 'fr', '/exemple');
        $this->setPrivateProperty($routeFr, 'id', 12);

        $this->routeRepository->findBy([
            'locales' => ['de', 'en'],
            'resourceKey' => 'examples',
            'resourceId' => '1',
        ])
            ->willReturn([$routeDe, $routeEn])
            ->shouldBeCalled();

        $this->routeGenerator->generate('/example', 'en', null)
            ->willReturn('/en/example')
            ->shouldBeCalled();

        $this->routeGenerator->generate('/beispiel', 'de', null)
            ->willReturn('/de/beispiel')
            ->shouldBeCalled();

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame([
            'de' => [
                'locale' => 'de',
                'url' => '/de/beispiel',
                'alternate' => true,
            ],
            'en' => [
                'locale' => 'en',
                'url' => '/en/example',
                'alternate' => true,
            ],
        ], $content['localizations'] ?? null);
    }

    public function testResolveWebspace(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setMainWebspace('sulu_io');

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame('sulu_io', $content['mainWebspace'] ?? null);
    }

    public function testResolveTemplateData(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setTemplateKey('default');
        $exampleDimension->setTemplateData(['exampleKey' => 'exampleValue']);

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame('default', $content['template'] ?? null);
    }

    public function testResolveAuthorData(): void
    {
        $author = new Contact();
        $this->setPrivateProperty($author, 'id', 1);
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setAuthored(new \DateTimeImmutable('2021-01-01'));
        $exampleDimension->setAuthor($author);
        $exampleDimension->setLastModified(new \DateTimeImmutable('2021-01-01'));

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        // Test author is now a ContentView with Reference
        $authorContentView = $content['author'] ?? null;
        self::assertInstanceOf(ContentView::class, $authorContentView);
        self::assertSame(1, $authorContentView->getContent());

        $references = $authorContentView->getReferences();
        self::assertCount(1, $references);
        self::assertSame(1, $references[0]->getResourceId());
        self::assertSame(UserInterface::RESOURCE_KEY, $references[0]->getResourceKey());

        self::assertSame('2021-01-01', $content['authored']?->format('Y-m-d'));
        self::assertSame('2021-01-01', $content['lastModified']?->format('Y-m-d'));
    }

    public function testResolveShadowData(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setShadowLocale('de');

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame('de', $content['shadowBaseLocale'] ?? null);
    }

    public function testResolveAuthorDataWithNullAuthor(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setAuthor(null);

        $result = $this->resolver->resolve($exampleDimension);
        self::assertInstanceOf(ContentView::class, $result);

        /** @var SettingsData $content */
        $content = $result->getContent();

        // Test author is ContentView with null content and empty references
        $authorContentView = $content['author'] ?? null;
        self::assertInstanceOf(ContentView::class, $authorContentView);
        self::assertNull($authorContentView->getContent());
        self::assertEmpty($authorContentView->getReferences());
    }
}
