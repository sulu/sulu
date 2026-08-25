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
 * Collects the content resolvers tagged `placement: root` and hands their keys to the normalizer,
 * which otherwise places every resolver's output under `extension`.
 *
 * @internal this class is not part of the public API and should only be called by the Symfony framework classes
 */
class ContentResolverPlacementPass implements CompilerPassInterface
{
    private const NORMALIZER_ID = 'sulu_content.content_view_data_normalizer';

    private const TAG = 'sulu_content.content_resolver';

    private const PLACEMENT_ROOT = 'root';

    private const PLACEMENT_EXTENSION = 'extension';

    /** The envelope's own keys plus everything SettingsResolver spreads into the root array. */
    private const RESERVED_ROOT_KEYS = [
        'resource', 'content', 'view', 'extension', 'settings',
        'availableLocales', 'localizations', 'mainWebspace', 'template',
        'author', 'authored', 'shadowBaseLocale', 'lastModified',
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::NORMALIZER_ID)) {
            return;
        }

        /** @var array<string, string> $rootKeys */
        $rootKeys = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!\is_array($attributes)) {
                    continue;
                }

                $placement = $attributes['placement'] ?? self::PLACEMENT_EXTENSION;

                if (self::PLACEMENT_EXTENSION === $placement) {
                    continue;
                }

                if (self::PLACEMENT_ROOT !== $placement) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s" declares unknown placement "%s" on tag "%s"; expected "%s" or "%s".',
                        $id,
                        \is_string($placement) ? $placement : \get_debug_type($placement),
                        self::TAG, self::PLACEMENT_ROOT, self::PLACEMENT_EXTENSION,
                    ));
                }

                $type = $attributes['type'] ?? null;
                if (!\is_string($type) || '' === $type) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s" declares placement "%s" without a "type" attribute on tag "%s".',
                        $id, self::PLACEMENT_ROOT, self::TAG,
                    ));
                }

                if (\in_array($type, self::RESERVED_ROOT_KEYS, true)) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s" cannot claim root key "%s": the key is reserved by the content envelope.',
                        $id, $type,
                    ));
                }

                if (isset($rootKeys[$type])) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Service "%s" cannot claim root key "%s": it is already claimed by "%s".',
                        $id, $type, $rootKeys[$type],
                    ));
                }

                $rootKeys[$type] = $id;
            }
        }

        $container->getDefinition(self::NORMALIZER_ID)
            ->setArgument('$rootResolverKeys', \array_keys($rootKeys));
    }
}
