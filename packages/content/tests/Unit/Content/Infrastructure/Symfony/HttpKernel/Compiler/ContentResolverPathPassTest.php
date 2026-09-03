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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\DataNormalizer\ContentViewDataNormalizer;
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ContentResolverPathPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[CoversClass(ContentResolverPathPass::class)]
class ContentResolverPathPassTest extends TestCase
{
    private const NORMALIZER_ID = 'sulu_content.content_view_data_normalizer';

    /**
     * @param array<string, class-string> $services service id => resolver class
     */
    private function containerWith(array $services): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(self::NORMALIZER_ID, new Definition(ContentViewDataNormalizer::class, [null, []]));

        foreach ($services as $id => $class) {
            $definition = new Definition($class);
            $definition->addTag('sulu_content.content_resolver');
            $container->setDefinition($id, $definition);
        }

        return $container;
    }

    /**
     * @return array<string, list<string>>
     */
    private function pathsOf(ContainerBuilder $container): array
    {
        /** @var array<string, list<string>> $paths */
        $paths = $container->getDefinition(self::NORMALIZER_ID)->getArgument(1);

        return $paths;
    }

    public function testBuildsMapFromTheResolverInterface(): void
    {
        $container = $this->containerWith([
            'acme.seo' => SeoTestResolver::class,
            'acme.settings' => SettingsTestResolver::class,
            'acme.product' => ProductTestResolver::class,
            'acme.product_content' => ProductContentTestResolver::class,
        ]);

        (new ContentResolverPathPass())->process($container);

        self::assertSame([
            'seo' => ['extension', 'seo'],
            'settings' => [],
            'product' => ['product'],
            'product_content' => ['product', 'content'],
        ], $this->pathsOf($container));
    }

    public function testResolversMaySharePath(): void
    {
        // settings already sits on [root], so a bundle adding its own root resolver must compile
        $container = $this->containerWith([
            'acme.settings' => SettingsTestResolver::class,
            'acme.other_root' => OtherRootTestResolver::class,
        ]);

        (new ContentResolverPathPass())->process($container);

        self::assertSame(['settings' => [], 'other_root' => []], $this->pathsOf($container));
    }

    public function testDuplicateTypeThrows(): void
    {
        $container = $this->containerWith([
            'acme.seo' => SeoTestResolver::class,
            'acme.seo_clone' => SeoCloneTestResolver::class,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/type "seo" which is already declared by "acme.seo"/');

        (new ContentResolverPathPass())->process($container);
    }

    public function testTaggedServiceNotImplementingTheInterfaceThrows(): void
    {
        $container = $this->containerWith(['acme.plain' => \stdClass::class]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/does not implement/');

        (new ContentResolverPathPass())->process($container);
    }

    public function testDoesNothingWithoutNormalizerDefinition(): void
    {
        $container = new ContainerBuilder();

        (new ContentResolverPathPass())->process($container);

        self::assertFalse($container->hasDefinition(self::NORMALIZER_ID));
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function provideInvalidOutputPaths(): iterable
    {
        yield 'property notation' => [PropertyNotationTestResolver::class, '/is invalid/'];
        yield 'without brackets' => [NoBracketsTestResolver::class, '/is invalid/'];
        yield 'not anchored at root' => [NotAnchoredTestResolver::class, '/must start with "\[root\]"/'];
        yield 'targets resource' => [ResourceTargetTestResolver::class, '/reserved key "resource"/'];
        yield 'view segment' => [ViewSegmentTestResolver::class, '/reserved segment "view"/'];
        yield 'content as non-final segment' => [ContentNonFinalTestResolver::class, '/non-final segment/'];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideInvalidOutputPaths')]
    public function testInvalidOutputPathThrows(string $class, string $messagePattern): void
    {
        $container = $this->containerWith(['acme.invalid' => $class]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($messagePattern);

        (new ContentResolverPathPass())->process($container);
    }

    public function testDecoratorClassIsRead(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('property_accessor', new Definition(PropertyAccessor::class));
        $normalizer = new Definition(ContentViewDataNormalizer::class, [new Reference('property_accessor'), []]);
        $normalizer->setPublic(true); // public so RemoveUnusedDefinitionsPass does not prune it
        $container->setDefinition(self::NORMALIZER_ID, $normalizer);

        $decorated = new Definition(ProductTestResolver::class);
        $decorated->addTag('sulu_content.content_resolver');
        $container->setDefinition('acme.product', $decorated);

        // the decorator moves the output somewhere else and its class is what counts
        $decorator = new Definition(ProductContentTestResolver::class);
        $decorator->setDecoratedService('acme.product');
        $container->setDefinition('acme.product_decorator', $decorator);

        $container->addCompilerPass(new ContentResolverPathPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->compile();

        self::assertSame(['product_content' => ['product', 'content']], $this->pathsOf($container));
    }
}

abstract class AbstractTestResolver implements ResolverInterface
{
    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        return null;
    }
}

class SeoTestResolver extends AbstractTestResolver
{
    public static function getType(): string
    {
        return 'seo';
    }

    public static function getOutputPath(): string
    {
        return '[root][extension][seo]';
    }
}

class SeoCloneTestResolver extends AbstractTestResolver
{
    public static function getType(): string
    {
        return 'seo';
    }

    public static function getOutputPath(): string
    {
        return '[root][extension][seo_clone]';
    }
}

class SettingsTestResolver extends AbstractTestResolver
{
    public static function getType(): string
    {
        return 'settings';
    }

    public static function getOutputPath(): string
    {
        return '[root]';
    }
}

class OtherRootTestResolver extends AbstractTestResolver
{
    public static function getType(): string
    {
        return 'other_root';
    }

    public static function getOutputPath(): string
    {
        return '[root]';
    }
}

class ProductTestResolver extends AbstractTestResolver
{
    public static function getType(): string
    {
        return 'product';
    }

    public static function getOutputPath(): string
    {
        return '[root][product]';
    }
}

class ProductContentTestResolver extends AbstractTestResolver
{
    public static function getType(): string
    {
        return 'product_content';
    }

    public static function getOutputPath(): string
    {
        return '[root][product][content]';
    }
}

abstract class AbstractInvalidPathTestResolver extends AbstractTestResolver
{
    public static function getType(): string
    {
        return 'invalid';
    }
}

class PropertyNotationTestResolver extends AbstractInvalidPathTestResolver
{
    public static function getOutputPath(): string
    {
        return '[root].product';
    }
}

class NoBracketsTestResolver extends AbstractInvalidPathTestResolver
{
    public static function getOutputPath(): string
    {
        return 'root';
    }
}

class NotAnchoredTestResolver extends AbstractInvalidPathTestResolver
{
    public static function getOutputPath(): string
    {
        return '[product]';
    }
}

class ResourceTargetTestResolver extends AbstractInvalidPathTestResolver
{
    public static function getOutputPath(): string
    {
        return '[root][resource]';
    }
}

class ViewSegmentTestResolver extends AbstractInvalidPathTestResolver
{
    public static function getOutputPath(): string
    {
        return '[root][view][x]';
    }
}

class ContentNonFinalTestResolver extends AbstractInvalidPathTestResolver
{
    public static function getOutputPath(): string
    {
        return '[root][content][x]';
    }
}
