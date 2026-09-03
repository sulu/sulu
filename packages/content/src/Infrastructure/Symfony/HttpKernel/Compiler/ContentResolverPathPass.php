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

use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads `getOutputPath()` off every `sulu_content.content_resolver` and writes the result into the
 * normalizer's `$paths` argument, a map from resolver type to path segments without the `[root]`
 * anchor. Two resolvers may share a path; the normalizer merges them by priority.
 * Runs after `DecoratorServicePass`, so a decorated service already carries its decorator's class
 * and both are read as one resolver.
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

        foreach (\array_keys($container->findTaggedServiceIds(self::TAG)) as $id) {
            $definition = $container->getDefinition($id);
            $class = $container->getParameterBag()->resolveValue($definition->getClass());

            if (!\is_string($class) || !\is_a($class, ResolverInterface::class, true)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Service "%s" is tagged "%s" but does not implement "%s".',
                    $id,
                    self::TAG,
                    ResolverInterface::class,
                ));
            }

            $type = $class::getType();
            $segments = $this->parsePath($id, $class::getOutputPath());

            if (isset($owners[$type]) && $owners[$type] !== $id) {
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

        $container->getDefinition(self::NORMALIZER_ID)->replaceArgument(1, $paths);
    }

    /**
     * @return list<string>
     */
    private function parsePath(string $id, string $path): array
    {
        if (1 !== \preg_match('/^(\[[A-Za-z0-9_]+\])+$/', $path)) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": output path "%s" is invalid; expected bracket segments like "[root][product]".',
                $id,
                $path,
            ));
        }

        \preg_match_all('/\[([A-Za-z0-9_]+)\]/', $path, $matches);
        $segments = $matches[1];

        if (self::ROOT !== \array_shift($segments)) {
            throw new \InvalidArgumentException(\sprintf('Service "%s": output path "%s" must start with "[root]".', $id, $path));
        }

        // `resource` is reserved only as the first segment.
        if ('resource' === ($segments[0] ?? null)) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": output path "%s" targets the reserved key "resource".',
                $id,
                $path,
            ));
        }

        if (\in_array('view', $segments, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": output path "%s" uses the reserved segment "view".',
                $id,
                $path,
            ));
        }

        $contentIndex = \array_search('content', $segments, true);
        if (false !== $contentIndex && $contentIndex !== \count($segments) - 1) {
            throw new \InvalidArgumentException(\sprintf(
                'Service "%s": output path "%s" has "content" as a non-final segment; it must be the last segment.',
                $id,
                $path,
            ));
        }

        return $segments;
    }
}
