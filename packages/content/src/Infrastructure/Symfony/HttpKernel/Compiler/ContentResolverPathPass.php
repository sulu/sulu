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

namespace Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads the `path` and `priority` attributes off `sulu_content.content_resolver` tags and writes
 * the result into the normalizer's `$paths` argument, a map from resolver type to envelope segments
 * without the `[root]` anchor. Runs after `DecoratorServicePass`, which has already moved a decorated
 * service's tag onto its decorator, so a decorated service is skipped here and the decorator's tag applies.
 *
 * @internal this class is not part of the public API and should only be called by the Symfony framework classes
 */
class ContentResolverPathPass implements CompilerPassInterface
{
    private const NORMALIZER_ID = 'sulu_content.content_view_data_normalizer';

    private const TAG = 'sulu_content.content_resolver';

    private const ROOT = 'root';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::NORMALIZER_ID)) {
            return;
        }

        /** @var array<string, list<string>> $paths */
        $paths = [];
        /** @var array<string, string> $owners */
        $owners = [];
        /** @var array<string, int> $priorities */
        $priorities = [];

        // Real shape differs from Symfony's plain `array` type.
        /** @var array<string, list<array<string, mixed>>> $tagged */
        $tagged = $container->findTaggedServiceIds(self::TAG);

        foreach ($tagged as $id => $tags) {
            $definition = $container->getDefinition($id);
            /** @var list<array{id?: string}> $decoratorTags */
            $decoratorTags = $definition->getTag('container.decorator');
            $decoratedId = $decoratorTags[0]['id'] ?? null;

            foreach ($tags as $attributes) {
                $implicitType = null;
                if (!isset($attributes['type'])) {
                    $class = $container->getParameterBag()->resolveValue($definition->getClass());
                    if (\is_string($class) && \class_exists($class)) {
                        $implicitType = $this->resolveImplicitType(new \ReflectionClass($class));
                    }
                }

                $type = $attributes['type'] ?? $implicitType ?? $decoratedId ?? $id;
                if (!\is_string($type) || '' === $type) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s": the "type" attribute of tag "%s" must be a non-empty string.',
                        $id,
                        self::TAG,
                    ));
                }

                if ((string) (int) $type === $type) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s": the "type" attribute "%s" of tag "%s" must not be a numeric string.',
                        $id,
                        $type,
                        self::TAG,
                    ));
                }

                $segments = \array_key_exists('path', $attributes)
                    ? $this->parsePath($id, $attributes['path'])
                    : $this->defaultPath($id, $type);

                $priority = $attributes['priority'] ?? 0;
                if (\is_string($priority) && 1 === \preg_match('/^-?\d+$/', $priority)) {
                    $priority = (int) $priority;
                }
                if (!\is_int($priority)) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s": the "priority" attribute of tag "%s" must be an integer.',
                        $id,
                        self::TAG,
                    ));
                }

                if (isset($owners[$type])
                    && ($owners[$type] !== $id || $paths[$type] !== $segments || $priorities[$type] !== $priority)
                ) {
                    if ($owners[$type] === $id) {
                        throw new \InvalidArgumentException(\sprintf(
                            'Service "%s" declares content resolver type "%s" twice with different attributes.',
                            $id,
                            $type,
                        ));
                    }

                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s" declares content resolver type "%s" which is already declared by "%s".',
                        $id,
                        $type,
                        $owners[$type],
                    ));
                }

                $paths[$type] = $segments;
                $owners[$type] = $id;
                $priorities[$type] = $priority;
            }
        }

        foreach ($paths as $type => $segments) {
            foreach ($paths as $otherType => $otherSegments) {
                if ($type === $otherType) {
                    continue;
                }

                if ([] !== $segments
                    && \count($segments) < \count($otherSegments)
                    && $segments === \array_slice($otherSegments, 0, \count($segments))
                ) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Content resolver "%s" (path "%s") is a prefix of "%s" (path "%s"); nested resolver paths are not supported.',
                        $type,
                        self::format($segments),
                        $otherType,
                        self::format($otherSegments),
                    ));
                }

                if ($segments === $otherSegments && $priorities[$type] === $priorities[$otherType]) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Content resolvers "%s" and "%s" share path "%s" with the same priority %d; set a distinct "priority" on one of them.',
                        $type,
                        $otherType,
                        self::format($segments),
                        $priorities[$type],
                    ));
                }
            }
        }

        $container->getDefinition(self::NORMALIZER_ID)->replaceArgument(1, $paths);
    }

    /**
     * Mirrors PriorityTaggedServiceTrait's index resolution for an untagged `type`: the class's
     * first `#[AsTaggedItem]` index, else its static `getDefaultTypeName()`, else null so the
     * caller falls back to the decorated id or the service id.
     *
     * @param \ReflectionClass<object> $reflection
     */
    private function resolveImplicitType(\ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(AsTaggedItem::class);
        if ([] !== $attributes) {
            $index = $attributes[0]->newInstance()->index;
            if (null !== $index) {
                return $index;
            }
        }

        if ($reflection->hasMethod('getDefaultTypeName')) {
            $method = $reflection->getMethod('getDefaultTypeName');
            if ($method->isStatic() && $method->isPublic()) {
                $value = $method->invoke(null);
                if (\is_string($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function defaultPath(string $id, string $type): array
    {
        $segments = ['extension', $type];

        if ('view' === $type) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": path "%s" uses the reserved segment "view".',
                $id,
                self::format($segments),
            ));
        }

        return $segments;
    }

    /**
     * @return list<string>
     */
    private function parsePath(string $id, mixed $path): array
    {
        if (!\is_string($path) || 1 !== \preg_match('/^(\[[A-Za-z0-9_]+\])+$/', $path)) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": path "%s" is invalid; expected bracket segments like "[root][product]".',
                $id,
                \is_string($path) ? $path : \get_debug_type($path),
            ));
        }

        \preg_match_all('/\[([A-Za-z0-9_]+)\]/', $path, $matches);
        $segments = $matches[1];

        if (self::ROOT !== \array_shift($segments)) {
            throw new \InvalidArgumentException(\sprintf('Service "%s": path "%s" must start with "[root]".', $id, $path));
        }

        // `resource` is reserved only as the first segment.
        $first = $segments[0] ?? null;
        if ('resource' === $first) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": path "%s" targets the reserved envelope key "%s".',
                $id,
                $path,
                $first,
            ));
        }

        if (\in_array('view', $segments, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": path "%s" uses the reserved segment "view".',
                $id,
                $path,
            ));
        }

        $contentIndex = \array_search('content', $segments, true);
        if (false !== $contentIndex && $contentIndex !== \count($segments) - 1) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": path "%s" has "content" as a non-final segment; a content slot must be the last segment.',
                $id,
                $path,
            ));
        }

        return $segments;
    }

    /**
     * @param list<string> $segments
     */
    private static function format(array $segments): string
    {
        return '[' . self::ROOT . ']' . ([] === $segments ? '' : '[' . \implode('][', $segments) . ']');
    }
}
