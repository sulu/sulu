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
 * Collects which template type each content resource uses. The content package cannot know its own
 * consumers, so pages, articles and snippets declare the pair on the tag.
 *
 * @internal
 */
final class ContentTemplateTypePass implements CompilerPassInterface
{
    public const TAG = 'sulu_content.content_template_type';

    public const PARAMETER = 'sulu_content.content_template_types';

    public function process(ContainerBuilder $container): void
    {
        $templateTypes = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $resourceKey = \is_array($attributes) ? ($attributes['resource-key'] ?? null) : null;
                $templateType = \is_array($attributes) ? ($attributes['template-type'] ?? null) : null;

                if (!\is_string($resourceKey) || !\is_string($templateType)) {
                    throw new \LogicException(\sprintf(
                        'Service "%s" is tagged "%s" but is missing a string "resource-key" or "template-type".',
                        $serviceId,
                        self::TAG,
                    ));
                }

                $templateTypes[$resourceKey] = $templateType;
            }
        }

        $container->setParameter(self::PARAMETER, $templateTypes);
    }
}
