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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Symfony\HttpKernel\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\DataNormalizer\ContentViewDataNormalizer;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ContentResolverPlacementPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(ContentResolverPlacementPass::class)]
class ContentResolverPlacementPassTest extends TestCase
{
    /**
     * @param array<string, string> ...$tags
     */
    private function containerWith(array ...$tags): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'sulu_content.content_view_data_normalizer',
            new Definition(ContentViewDataNormalizer::class),
        );

        foreach ($tags as $index => $attributes) {
            $definition = new Definition(\stdClass::class);
            $definition->addTag('sulu_content.content_resolver', $attributes);
            $container->setDefinition('resolver_' . $index, $definition);
        }

        return $container;
    }

    public function testCollectsRootPlacements(): void
    {
        $container = $this->containerWith(
            ['type' => 'product', 'placement' => 'root'],
            ['type' => 'seo'],
        );

        (new ContentResolverPlacementPass())->process($container);

        self::assertSame(
            ['product'],
            $container->getDefinition('sulu_content.content_view_data_normalizer')
                ->getArgument('$rootResolverKeys'),
        );
    }

    public function testResolversWithoutPlacementAreNotCollected(): void
    {
        $container = $this->containerWith(['type' => 'seo'], ['type' => 'excerpt']);

        (new ContentResolverPlacementPass())->process($container);

        self::assertSame(
            [],
            $container->getDefinition('sulu_content.content_view_data_normalizer')
                ->getArgument('$rootResolverKeys'),
        );
    }

    public function testUnknownPlacementThrows(): void
    {
        $container = $this->containerWith(['type' => 'product', 'placement' => 'sideways']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"sideways"/');

        (new ContentResolverPlacementPass())->process($container);
    }

    public function testRootPlacementCollidingWithAReservedKeyThrows(): void
    {
        $container = $this->containerWith(['type' => 'author', 'placement' => 'root']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/reserved/');

        (new ContentResolverPlacementPass())->process($container);
    }

    public function testRootPlacementCollidingWithSettingsThrows(): void
    {
        $container = $this->containerWith(['type' => 'settings', 'placement' => 'root']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/reserved/');

        (new ContentResolverPlacementPass())->process($container);
    }

    public function testTwoRootResolversSharingATypeThrow(): void
    {
        $container = $this->containerWith(
            ['type' => 'product', 'placement' => 'root'],
            ['type' => 'product', 'placement' => 'root'],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already/');

        (new ContentResolverPlacementPass())->process($container);
    }

    public function testRootPlacementWithoutATypeThrows(): void
    {
        $container = $this->containerWith(['placement' => 'root']);

        $this->expectException(\InvalidArgumentException::class);

        (new ContentResolverPlacementPass())->process($container);
    }

    public function testDoesNothingWithoutTheNormalizerDefinition(): void
    {
        $container = new ContainerBuilder();

        (new ContentResolverPlacementPass())->process($container);

        self::assertFalse($container->hasDefinition('sulu_content.content_view_data_normalizer'));
    }
}
