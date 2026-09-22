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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ContentLocalizationsResolver\ContentLocalizationsResolver;
use Sulu\Content\Application\ContentLocalizationsResolver\ContentLocalizationsResolverInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ContentLocalizationsResolverTest extends TestCase
{
    use ProphecyTrait;

    private const LOCALIZATIONS = ['en' => ['url' => '/en/my-example', 'locale' => 'en', 'alternate' => true]];

    public function testTheResolverOfTheResourceKeyResolves(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $exampleResolver = $this->prophesize(ContentLocalizationsResolverInterface::class);
        $exampleResolver->resolve($dimensionContent, 'sulu-io')->willReturn(self::LOCALIZATIONS)->shouldBeCalled();

        $defaultResolver = $this->prophesize(ContentLocalizationsResolverInterface::class);
        $defaultResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $resolver = new ContentLocalizationsResolver(
            new ServiceLocator(['examples' => static fn () => $exampleResolver->reveal()]),
            $defaultResolver->reveal(),
        );

        $this->assertSame(self::LOCALIZATIONS, $resolver->resolve($dimensionContent, 'sulu-io'));
    }

    public function testContentWithoutResolverOfItsOwnGetsTheDefault(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $defaultResolver = $this->prophesize(ContentLocalizationsResolverInterface::class);
        $defaultResolver->resolve($dimensionContent, 'sulu-io')->willReturn(self::LOCALIZATIONS)->shouldBeCalled();

        $resolver = new ContentLocalizationsResolver(new ServiceLocator([]), $defaultResolver->reveal());

        $this->assertSame(self::LOCALIZATIONS, $resolver->resolve($dimensionContent, 'sulu-io'));
    }
}
