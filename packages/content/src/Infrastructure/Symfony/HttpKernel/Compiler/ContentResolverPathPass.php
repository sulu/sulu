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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads the `path` attribute off `sulu_content.content_resolver` tags and writes the result into
 * the normalizer's `$paths` argument, a map from resolver type to path segments without the
 * `[root]` anchor. Two resolvers may share a path; the normalizer merges them by priority.
 * Runs after `DecoratorServicePass`, which has already moved a decorated service's tag onto its
 * decorator, so a decorated service is skipped here and the decorator's tag applies.
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

                if (isset($owners[$type]) && ($owners[$type] !== $id || $paths[$type] !== $segments)) {
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
            }
        }

        $container->getDefinition(self::NORMALIZER_ID)->replaceArgument(1, $paths);
    }

    /**
     * Mirrors PriorityTaggedServiceTrait's index resolution for an untagged `type`: the class's
     * static `getDefaultTypeName()`, else null so the caller falls back to the decorated id or
     * the service id.
     *
     * @param \ReflectionClass<object> $reflection
     */
    private function resolveImplicitType(\ReflectionClass $reflection): ?string
    {
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
                'Service "%s": path "%s" targets the reserved key "%s".',
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
                'Service "%s": path "%s" has "content" as a non-final segment; it must be the last segment.',
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
