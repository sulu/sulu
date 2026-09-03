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
     * @param array<string, array<string, mixed>> $services service id => tag attributes
     */
    private function containerWith(array $services): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(self::NORMALIZER_ID, new Definition(ContentViewDataNormalizer::class, [null, []]));

        foreach ($services as $id => $attributes) {
            $class = $attributes['__class'] ?? \stdClass::class;
            unset($attributes['__class']);
            if (!\is_string($class)) {
                throw new \LogicException('The "__class" fixture attribute must be a class-string.');
            }
            $definition = new Definition($class);
            $definition->addTag('sulu_content.content_resolver', $attributes);
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

    public function testBuildsMapWithDefaultsAndExplicitPaths(): void
    {
        $container = $this->containerWith([
            'template' => ['type' => 'template', 'path' => '[root][content]'],
            'settings' => ['type' => 'settings', 'path' => '[root]'],
            'seo' => ['type' => 'seo'],
            'product' => ['type' => 'product', 'path' => '[root][product][content]'],
            'shop' => ['type' => 'shop', 'path' => '[root][shop][meta]'],
        ]);

        (new ContentResolverPathPass())->process($container);

        self::assertSame([
            'template' => ['content'],
            'settings' => [],
            'seo' => ['extension', 'seo'],
            'product' => ['product', 'content'],
            'shop' => ['shop', 'meta'],
        ], $this->pathsOf($container));
    }

    public function testTypeFallsBackToServiceId(): void
    {
        $container = $this->containerWith(['acme.resolver' => []]);

        (new ContentResolverPathPass())->process($container);

        self::assertSame(['acme.resolver' => ['extension', 'acme.resolver']], $this->pathsOf($container));
    }

    public function testSameServiceTaggedTwiceIdenticallyIsAllowed(): void
    {
        $container = $this->containerWith([]);
        $definition = new Definition(\stdClass::class);
        $definition->addTag('sulu_content.content_resolver', ['type' => 'seo']);
        $definition->addTag('sulu_content.content_resolver', ['type' => 'seo']);
        $container->setDefinition('seo', $definition);

        (new ContentResolverPathPass())->process($container);

        self::assertSame(['seo' => ['extension', 'seo']], $this->pathsOf($container));
    }

    public function testSameServiceTaggedTwiceWithDifferentAttributesThrows(): void
    {
        $container = $this->containerWith([]);
        $definition = new Definition(\stdClass::class);
        $definition->addTag('sulu_content.content_resolver', ['type' => 'seo']);
        $definition->addTag('sulu_content.content_resolver', ['type' => 'seo', 'path' => '[root][seo]']);
        $container->setDefinition('seo', $definition);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/twice with different attributes/');

        (new ContentResolverPathPass())->process($container);
    }

    public function testDoesNothingWithoutNormalizerDefinition(): void
    {
        $container = new ContainerBuilder();

        (new ContentResolverPathPass())->process($container);

        self::assertFalse($container->hasDefinition(self::NORMALIZER_ID));
    }

    /**
     * @return iterable<string, array{array<string, array<string, mixed>>, string}>
     */
    public static function provideInvalidConfigurations(): iterable
    {
        yield 'path with property notation' => [['a' => ['type' => 'a', 'path' => '[root].product']], '/invalid/'];
        yield 'path without brackets' => [['a' => ['type' => 'a', 'path' => 'root']], '/invalid/'];
        yield 'path with null-safe segment' => [['a' => ['type' => 'a', 'path' => '[root?][product]']], '/invalid/'];
        yield 'path not anchored at root' => [['a' => ['type' => 'a', 'path' => '[product]']], '/must start with "\[root\]"/'];
        yield 'path targets resource' => [['a' => ['type' => 'a', 'path' => '[root][resource]']], '/reserved key "resource"/'];
        yield 'path targets view' => [['a' => ['type' => 'a', 'path' => '[root][view][x]']], '/reserved segment "view"/'];
        yield 'path nested below content' => [['a' => ['type' => 'a', 'path' => '[root][content][x]']], '/non-final segment/'];
        yield 'content as non-final segment' => [['a' => ['type' => 'a', 'path' => '[root][product][content][x]']], '/non-final segment/'];
        yield 'view as inner segment' => [['a' => ['type' => 'a', 'path' => '[root][product][view]']], '/reserved segment "view"/'];
        yield 'same type on two services' => [
            ['a' => ['type' => 'seo'], 'b' => ['type' => 'seo']],
            '/type "seo" which is already declared by "a"/',
        ];
        yield 'empty type' => [['a' => ['type' => '']], '/"type" attribute/'];
        yield 'numeric string type' => [['a' => ['type' => '0']], '/numeric string/'];
        yield 'type view without path reserved' => [['a' => ['type' => 'view']], '/reserved/'];
        yield 'implicit index collides with explicit type' => [
            [
                'a' => ['type' => 'fixture_item'],
                'b' => ['__class' => ContentResolverPathPassTestFixtureWithGetDefaultTypeName::class],
            ],
            '/type "fixture_item" which is already declared by "a"/',
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $services
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidConfigurations')]
    public function testInvalidConfigurationThrows(array $services, string $messagePattern): void
    {
        $container = $this->containerWith($services);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($messagePattern);

        (new ContentResolverPathPass())->process($container);
    }

    public function testResolversMaySharePath(): void
    {
        // settings already sits on [root], so a bundle adding its own root resolver must compile
        $container = $this->containerWith([
            'a' => ['type' => 'a', 'path' => '[root]'],
            'b' => ['type' => 'b', 'path' => '[root]'],
            'c' => ['type' => 'c', 'path' => '[root][product]'],
            'd' => ['type' => 'd', 'path' => '[root][product][details]'],
        ]);

        (new ContentResolverPathPass())->process($container);

        self::assertSame(
            ['a' => [], 'b' => [], 'c' => ['product'], 'd' => ['product', 'details']],
            $this->pathsOf($container),
        );
    }

    public function testDecoratorReDeclaringTagCompiles(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('property_accessor', new Definition(PropertyAccessor::class));
        $normalizerDefinition = new Definition(ContentViewDataNormalizer::class, [new Reference('property_accessor'), []]);
        // Public so RemoveUnusedDefinitionsPass does not prune it.
        $normalizerDefinition->setPublic(true);
        $container->setDefinition(self::NORMALIZER_ID, $normalizerDefinition);

        $container->setDefinition('sulu_content.template_resolver', new Definition(\stdClass::class));

        $routableTemplateResolver = new Definition(\stdClass::class);
        $routableTemplateResolver->setDecoratedService('sulu_content.template_resolver');
        $routableTemplateResolver->addTag('sulu_content.content_resolver', ['type' => 'template', 'path' => '[root][content]']);
        $container->setDefinition('sulu_content.routable_template_resolver', $routableTemplateResolver);

        $templateDecorator = new Definition(\stdClass::class);
        $templateDecorator->setDecoratedService('sulu_content.routable_template_resolver');
        $templateDecorator->addTag('sulu_content.content_resolver', ['type' => 'template', 'path' => '[root][content]']);
        $container->setDefinition('app.template_decorator', $templateDecorator);

        $seoResolver = new Definition(\stdClass::class);
        $seoResolver->addTag('sulu_content.content_resolver', ['type' => 'seo']);
        $container->setDefinition('sulu_content.seo_resolver', $seoResolver);

        $seoDecorator = new Definition(\stdClass::class);
        $seoDecorator->setDecoratedService('sulu_content.seo_resolver');
        $seoDecorator->addTag('sulu_content.content_resolver', ['type' => 'seo', 'path' => '[root][seo]']);
        $container->setDefinition('app.seo_decorator', $seoDecorator);

        $container->addCompilerPass(new ContentResolverPathPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->compile();

        /** @var array<string, list<string>> $paths */
        $paths = $container->getDefinition(self::NORMALIZER_ID)->getArgument(1);

        self::assertEqualsCanonicalizing(['template' => ['content'], 'seo' => ['seo']], $paths);
    }

    /**
     * Compiles a real container, so DecoratorServicePass runs and collapses the decoration chain
     * before ContentResolverPathPass reads the tags.
     */
    private function compiledContainerWith(callable $configure): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('property_accessor', new Definition(PropertyAccessor::class));
        $normalizer = new Definition(ContentViewDataNormalizer::class, [new Reference('property_accessor'), []]);
        $normalizer->setPublic(true); // public so RemoveUnusedDefinitionsPass does not prune it
        $container->setDefinition(self::NORMALIZER_ID, $normalizer);

        $configure($container);

        $container->addCompilerPass(new ContentResolverPathPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->compile();

        return $container;
    }

    public function testTwoDecoratorsOfOneServiceCompile(): void
    {
        $container = $this->compiledContainerWith(function(ContainerBuilder $container): void {
            $container->setDefinition('sulu_content.template_resolver', new Definition(\stdClass::class));

            foreach (['bundle_a.template_decorator' => 10, 'bundle_b.template_decorator' => 5] as $id => $priority) {
                $decorator = new Definition(\stdClass::class);
                $decorator->setDecoratedService('sulu_content.template_resolver', null, $priority);
                $decorator->addTag('sulu_content.content_resolver', ['type' => 'template', 'path' => '[root][content]']);
                $container->setDefinition($id, $decorator);
            }
        });

        self::assertSame(['template' => ['content']], $this->pathsOf($container));
    }

    public function testDecoratorWithoutTypeIsKeyedLikeTheTaggedIterator(): void
    {
        $container = $this->compiledContainerWith(function(ContainerBuilder $container): void {
            $resolver = new Definition(\stdClass::class);
            $resolver->addTag('sulu_content.content_resolver', ['type' => 'seo']);
            $container->setDefinition('sulu_content.seo_resolver', $resolver);

            $decorator = new Definition(\stdClass::class);
            $decorator->setDecoratedService('sulu_content.seo_resolver');
            $decorator->addTag('sulu_content.content_resolver', ['path' => '[root][seo]']);
            $container->setDefinition('app.seo_decorator', $decorator);
        });

        // PriorityTaggedServiceTrait indexes an untyped tag by the decorated id.
        self::assertSame(['sulu_content.seo_resolver' => ['seo']], $this->pathsOf($container));
    }

    public function testUntypedTagOnClassWithGetDefaultTypeNameUsesItsName(): void
    {
        $container = $this->containerWith([]);
        $definition = new Definition(ContentResolverPathPassTestFixtureWithGetDefaultTypeName::class);
        $definition->addTag('sulu_content.content_resolver', []);
        $container->setDefinition('acme.default_type_name_resolver', $definition);

        (new ContentResolverPathPass())->process($container);

        self::assertSame(['fixture_item' => ['extension', 'fixture_item']], $this->pathsOf($container));
    }

    public function testUntypedTagOnClassWithoutImplicitIndexFallsBackToServiceId(): void
    {
        $container = $this->containerWith([]);
        $definition = new Definition(\stdClass::class);
        $definition->addTag('sulu_content.content_resolver', []);
        $container->setDefinition('acme.plain_resolver', $definition);

        (new ContentResolverPathPass())->process($container);

        self::assertSame(['acme.plain_resolver' => ['extension', 'acme.plain_resolver']], $this->pathsOf($container));
    }
}

class ContentResolverPathPassTestFixtureWithGetDefaultTypeName
{
    public static function getDefaultTypeName(): string
    {
        return 'fixture_item';
    }
}
